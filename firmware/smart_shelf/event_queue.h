#pragma once

#include <ctype.h>
#include <stdint.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

// Antrean event dengan event_id tetap. Retry tidak membuat id baru.
// Kapasitas penuh menolak event baru dan mempertahankan event lama.

struct QueuedEvent {
  char eventId[16];
  char eventType[20];
  float weightGrams;
  char timestamp[21];
  uint32_t capturedMillis;
  uint8_t attempts;
  uint8_t timeValid;
};

class EventQueue {
 public:
  static const int kCapacity = 16;

  EventQueue() : count_(0) { memset(items_, 0, sizeof(items_)); }

  bool enqueue(const QueuedEvent& event) {
    if (event.eventId[0] == '\0') {
      return false;
    }
    if (findId(event.eventId) >= 0) {
      return false;
    }
    if (count_ >= kCapacity) {
      return false;
    }
    items_[count_++] = event;
    return true;
  }

  const QueuedEvent* head() const { return count_ == 0 ? nullptr : &items_[0]; }

  const QueuedEvent* at(int index) const {
    if (index < 0 || index >= count_) {
      return nullptr;
    }
    return &items_[index];
  }

  void popHead() {
    if (count_ == 0) {
      return;
    }
    for (int i = 1; i < count_; i++) {
      items_[i - 1] = items_[i];
    }
    count_--;
    memset(&items_[count_], 0, sizeof(QueuedEvent));
  }

  void markHeadAttempt() {
    if (count_ == 0) {
      return;
    }
    if (items_[0].attempts < 255) {
      items_[0].attempts++;
    }
  }

  void setHeadTimestamp(const char* timestamp) {
    if (count_ == 0 || timestamp == nullptr) {
      return;
    }
    strncpy(items_[0].timestamp, timestamp, sizeof(items_[0].timestamp) - 1);
    items_[0].timestamp[sizeof(items_[0].timestamp) - 1] = '\0';
    items_[0].timeValid = 1;
  }

  int size() const { return count_; }

  bool saveToText(char* out, size_t capacity) const {
    if (capacity == 0) {
      return false;
    }
    size_t used = 0;
    out[0] = '\0';
    for (int i = 0; i < count_; i++) {
      char line[96];
      snprintf(line, sizeof(line), "%s|%s|%.1f|%s|%lu|%u|%u\n", items_[i].eventId,
               items_[i].eventType, items_[i].weightGrams, items_[i].timestamp,
               (unsigned long) items_[i].capturedMillis, items_[i].attempts,
               items_[i].timeValid);
      size_t length = strlen(line);
      if (used + length + 1 > capacity) {
        return false;
      }
      memcpy(out + used, line, length + 1);
      used += length;
    }
    return true;
  }

  void loadFromText(const char* text) {
    count_ = 0;
    memset(items_, 0, sizeof(items_));
    if (text == nullptr) {
      return;
    }
    char buffer[1536];
    strncpy(buffer, text, sizeof(buffer) - 1);
    buffer[sizeof(buffer) - 1] = '\0';

    char* cursor = buffer;
    while (*cursor != '\0' && count_ < kCapacity) {
      char* line = cursor;
      char* newline = strchr(cursor, '\n');
      if (newline != nullptr) {
        *newline = '\0';
        cursor = newline + 1;
      } else {
        cursor += strlen(cursor);
      }
      if (line[0] == '\0') {
        continue;
      }
      char* fields[7];
      if (!splitPipe(line, fields, 7)) {
        continue;
      }
      QueuedEvent event;
      memset(&event, 0, sizeof(event));
      strncpy(event.eventId, fields[0], sizeof(event.eventId) - 1);
      strncpy(event.eventType, fields[1], sizeof(event.eventType) - 1);
      event.weightGrams = (float) atof(fields[2]);
      strncpy(event.timestamp, fields[3], sizeof(event.timestamp) - 1);
      event.capturedMillis = (uint32_t) strtoul(fields[4], nullptr, 10);
      event.attempts = (uint8_t) atoi(fields[5]);
      event.timeValid = (uint8_t) atoi(fields[6]);
      items_[count_++] = event;
    }
  }

 private:
  QueuedEvent items_[kCapacity];
  int count_;

  int findId(const char* eventId) const {
    for (int i = 0; i < count_; i++) {
      if (strcmp(items_[i].eventId, eventId) == 0) {
        return i;
      }
    }
    return -1;
  }

  static bool splitPipe(char* line, char* fields[], int expected) {
    int count = 0;
    fields[0] = line;
    count = 1;
    for (char* pointer = line; *pointer != '\0'; pointer++) {
      if (*pointer == '|') {
        if (count >= expected) {
          return false;
        }
        *pointer = '\0';
        fields[count++] = pointer + 1;
      }
    }
    return count == expected;
  }
};
