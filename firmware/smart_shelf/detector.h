#pragma once

#include <math.h>
#include <stdint.h>
#include <string.h>

// Logika deteksi yang sama diuji di firmware/tests/detector_spec.php.
// Perubahan stabil menurunkan berat menjadi ITEM_REMOVED, menaikkan berat menjadi ITEM_ADDED,
// lalu status kembali NORMAL pada berat yang baru.

enum ShelfState {
  SHELF_NORMAL = 0,
  SHELF_CHANGE_DETECTED,
  SHELF_VERIFYING
};

struct SampleResult {
  ShelfState state;
  bool eventEmitted;
  char eventType[16];
  float weightGrams;
  float baselineGrams;
};

class ShelfDetector {
 public:
  ShelfDetector(float thresholdGrams, uint32_t verifyMs)
      : thresholdGrams_(thresholdGrams),
        verifyMs_(verifyMs),
        index_(0),
        filled_(false),
        baselineReady_(false),
        baseline_(0),
        changeStartedMs_(0),
        state_(SHELF_NORMAL) {
    memset(window_, 0, sizeof(window_));
  }

  void setThreshold(float grams) {
    if (grams < 1.0f) {
      grams = 1.0f;
    }
    thresholdGrams_ = grams;
  }

  void setVerifyMs(uint32_t verifyMs) {
    if (verifyMs < 200) {
      verifyMs = 200;
    }
    if (verifyMs > 10000) {
      verifyMs = 10000;
    }
    verifyMs_ = verifyMs;
  }

  ShelfState state() const { return state_; }

  void resetBaseline() {
    baselineReady_ = false;
    baseline_ = 0;
    filled_ = false;
    index_ = 0;
    changeStartedMs_ = 0;
    state_ = SHELF_NORMAL;
    memset(window_, 0, sizeof(window_));
  }

  SampleResult update(uint32_t nowMs, float sample) {
    push(sample);

    SampleResult result;
    clearResult(&result);
    result.weightGrams = median();
    result.baselineGrams = baseline_;
    result.state = state_;

    if (!baselineReady_) {
      baseline_ = result.weightGrams;
      baselineReady_ = true;
      state_ = SHELF_NORMAL;
      result.baselineGrams = baseline_;
      result.state = state_;
      return result;
    }

    switch (state_) {
      case SHELF_NORMAL:
        if (departedFromBaseline(result.weightGrams)) {
          state_ = SHELF_CHANGE_DETECTED;
          changeStartedMs_ = nowMs;
        }
        break;
      case SHELF_CHANGE_DETECTED:
        state_ = SHELF_VERIFYING;
        evaluateVerifying(nowMs, result);
        break;
      case SHELF_VERIFYING:
        evaluateVerifying(nowMs, result);
        break;
    }

    result.state = state_;
    result.baselineGrams = baseline_;
    return result;
  }

 private:
  static const int kWindow = 5;

  float thresholdGrams_;
  uint32_t verifyMs_;
  float window_[kWindow];
  int index_;
  bool filled_;
  bool baselineReady_;
  float baseline_;
  uint32_t changeStartedMs_;
  ShelfState state_;

  void push(float sample) {
    if (!filled_) {
      for (int i = 0; i < kWindow; i++) {
        window_[i] = sample;
      }
      filled_ = true;
      index_ = 0;
      return;
    }
    window_[index_] = sample;
    index_ = (index_ + 1) % kWindow;
  }

  float median() const {
    float copy[kWindow];
    memcpy(copy, window_, sizeof(copy));
    for (int i = 0; i < kWindow; i++) {
      for (int j = i + 1; j < kWindow; j++) {
        if (copy[j] < copy[i]) {
          float swap = copy[i];
          copy[i] = copy[j];
          copy[j] = swap;
        }
      }
    }
    return copy[kWindow / 2];
  }

  bool departedFromBaseline(float weight) const {
    return fabsf(weight - baseline_) > thresholdGrams_;
  }

  uint32_t elapsed(uint32_t nowMs) const { return nowMs - changeStartedMs_; }

  void evaluateVerifying(uint32_t nowMs, SampleResult& result) {
    if (!departedFromBaseline(result.weightGrams)) {
      state_ = SHELF_NORMAL;
      return;
    }
    if (elapsed(nowMs) < verifyMs_) {
      return;
    }
    bool removed = result.weightGrams < baseline_;
    baseline_ = result.weightGrams;
    state_ = SHELF_NORMAL;
    result.baselineGrams = baseline_;
    emit(&result, removed ? "ITEM_REMOVED" : "ITEM_ADDED");
  }

  static void clearResult(SampleResult* result) {
    memset(result, 0, sizeof(*result));
  }

  static void emit(SampleResult* result, const char* type) {
    result->eventEmitted = true;
    strncpy(result->eventType, type, sizeof(result->eventType) - 1);
  }
};

inline const char* shelfStateName(ShelfState state) {
  switch (state) {
    case SHELF_NORMAL:
      return "NORMAL";
    case SHELF_CHANGE_DETECTED:
      return "CHANGE_DETECTED";
    case SHELF_VERIFYING:
      return "VERIFYING";
  }
  return "UNKNOWN";
}
