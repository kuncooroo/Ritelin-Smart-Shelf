#include <HX711.h>
#include <HTTPClient.h>
#include <Preferences.h>
#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <time.h>

#include "ble_provision.h"
#include "config.h"
#include "detector.h"
#include "event_queue.h"

#if POWER_SAVE
#include <esp_sleep.h>
#include <esp_wifi.h>
#endif

HX711 scale;
Preferences prefs;
ShelfDetector detector(DEFAULT_THRESHOLD_GRAMS, DEFAULT_VERIFY_MS);
EventQueue queue;

bool calibrated = false;
bool provisioning = false;
bool ntpStarted = false;
char activeSsid[33];
char activePassword[65];
char activeToken[81];
char line[96];
uint8_t lineLength = 0;
uint32_t lastSampleMs = 0;
uint32_t lastStatusMs = 0;
uint32_t lastUploadMs = 0;
uint32_t lastConfigMs = 0;
uint32_t lastWifiTryMs = 0;
long lastRaw = 0;
float lastGrams = 0;

void copyPref(const char* key, const char* fallback, char* dest, size_t size) {
  String value = prefs.getString(key, fallback);
  strncpy(dest, value.c_str(), size - 1);
  dest[size - 1] = '\0';
}

bool clockValid() { return time(nullptr) > 1700000000; }

void formatTimestamp(char* out, size_t size, time_t when) {
  struct tm parts {};
  gmtime_r(&when, &parts);
  strftime(out, size, "%Y-%m-%dT%H:%M:%SZ", &parts);
}

void persistQueue() {
  char buffer[1536];
  if (!queue.saveToText(buffer, sizeof(buffer))) {
    Serial.println("antrean gagal disimpan");
    return;
  }
  prefs.putString("queue", buffer);
}

void loadIdentity() {
  copyPref("ssid", WIFI_SSID, activeSsid, sizeof(activeSsid));
  copyPref("pass", WIFI_PASSWORD, activePassword, sizeof(activePassword));
  copyPref("token", DEVICE_TOKEN, activeToken, sizeof(activeToken));
}

void loadCalibration() {
  calibrated = prefs.getBool("calibrated", false);
  if (!calibrated) {
    return;
  }
  scale.set_scale(prefs.getFloat("factor", 1));
  scale.set_offset((long) prefs.getLong("offset", 0));
}

void saveCalibration(float factor) {
  prefs.putFloat("factor", factor);
  prefs.putLong("offset", scale.get_offset());
  prefs.putBool("calibrated", true);
  calibrated = true;
}

void formatEventId(char* out, size_t size) {
  uint32_t next = prefs.getUInt("evt", 0) + 1;
  prefs.putUInt("evt", next);
  snprintf(out, size, "evt-%06lu", (unsigned long) next);
}

void printJson(const QueuedEvent& event) {
  Serial.printf(
      "{\"device_id\":\"%s\",\"event_id\":\"%s\",\"event_type\":\"%s\",\"weight_grams\":%.1f,\"timestamp\":\"%s\"}\n",
      DEVICE_ID, event.eventId, event.eventType, event.weightGrams, event.timestamp);
}

void rememberEvent(const SampleResult& result) {
  QueuedEvent event;
  memset(&event, 0, sizeof(event));
  formatEventId(event.eventId, sizeof(event.eventId));
  strncpy(event.eventType, result.eventType, sizeof(event.eventType) - 1);
  event.weightGrams = result.weightGrams;
  event.capturedMillis = millis();
  if (clockValid()) {
    formatTimestamp(event.timestamp, sizeof(event.timestamp), time(nullptr));
    event.timeValid = 1;
    printJson(event);
  } else {
    Serial.printf("event_id=%s event_type=%s weight_g=%.1f timestamp=menunggu_sinkron_waktu\n",
                  event.eventId, event.eventType, event.weightGrams);
  }
  if (!queue.enqueue(event)) {
    Serial.println("antrean penuh atau event_id sudah ada; event lama tetap disimpan");
  }
  persistQueue();
}

void printReading(long raw, float grams, const SampleResult* result) {
  bool due = millis() - lastStatusMs >= STATUS_INTERVAL_MS;
  bool event = result != nullptr && result->eventEmitted;
  if (!due && !event) {
    return;
  }
  lastStatusMs = millis();
  const char* state = calibrated && result != nullptr ? shelfStateName(result->state) : "BELUM_KALIBRASI";
  const char* eventName = event ? result->eventType : "NONE";
  Serial.printf("device_id=%s raw=%ld weight_g=%.1f state=%s event=%s\n", DEVICE_ID, raw, grams,
                state, eventName);
}

void sampleScale() {
  if (!scale.is_ready()) {
    return;
  }
  long raw = scale.read();
  lastRaw = raw;
  if (!calibrated || scale.get_scale() == 0) {
    printReading(raw, 0, nullptr);
    return;
  }
  float grams = (raw - scale.get_offset()) / scale.get_scale();
  lastGrams = grams;
  SampleResult result = detector.update(millis(), grams);
  printReading(raw, grams, &result);
  if (result.eventEmitted) {
    rememberEvent(result);
  }
}

void calibrateWithKnownMass(float knownGrams) {
  if (knownGrams <= 0) {
    Serial.println("kalibrasi ditolak: massa referensi harus lebih dari 0 gram");
    return;
  }
  scale.set_scale(1);
  float reading = scale.get_units(10);
  if (fabsf(reading) < 1) {
    Serial.println("kalibrasi ditolak: perubahan raw terlalu kecil");
    return;
  }
  float factor = reading / knownGrams;
  scale.set_scale(factor);
  saveCalibration(factor);
  Serial.printf("faktor kalibrasi %.4f\n", factor);
}

void printStatusCommand() {
  Serial.printf("device_id=%s firmware=%s kalibrasi=%s faktor=%.4f antrean=%d wifi=%s\n",
                DEVICE_ID, DEVICE_FIRMWARE, calibrated ? "ya" : "belum", scale.get_scale(),
                queue.size(), WiFi.status() == WL_CONNECTED ? "terhubung" : "terputus");
  Serial.println("token perangkat tidak ditampilkan");
}

void performTare() {
  scale.tare(10);
  prefs.putLong("offset", scale.get_offset());
  detector.resetBaseline();
  Serial.println("tare selesai, offset tersimpan");
}

void handleCommand(char* command) {
  if (strcmp(command, "tare") == 0) {
    performTare();
    reportTare();
    return;
  }
  if (strncmp(command, "calibrate ", 10) == 0) {
    calibrateWithKnownMass((float) atof(command + 10));
    return;
  }
  if (strncmp(command, "factor ", 7) == 0) {
    float factor = (float) atof(command + 7);
    if (factor == 0) {
      Serial.println("faktor tidak valid");
      return;
    }
    scale.set_scale(factor);
    saveCalibration(factor);
    Serial.println("faktor disimpan");
    return;
  }
  if (strcmp(command, "provision") == 0) {
    provisioning = true;
    bleProvisionStart(DEVICE_ID);
    Serial.println("BLE aktif. Tulis SSID, sandi, dan token, lalu tulis 1 pada karakteristik commit.");
    return;
  }
  if (strcmp(command, "status") == 0) {
    printStatusCommand();
    return;
  }
  Serial.println("perintah: tare | calibrate <gram> | factor <nilai> | provision | status");
}

void handleSerial() {
  while (Serial.available() > 0) {
    char character = (char) Serial.read();
    if (character == '\n' || character == '\r') {
      if (lineLength > 0) {
        line[lineLength] = '\0';
        handleCommand(line);
        lineLength = 0;
      }
      continue;
    }
    if (lineLength < sizeof(line) - 1) {
      line[lineLength++] = character;
    }
  }
}

void serviceProvisioning() {
  if (!provisioning) {
    return;
  }
  char ssid[33];
  char password[65];
  char token[81];
  if (!bleProvisionTake(ssid, sizeof(ssid), password, sizeof(password), token, sizeof(token))) {
    return;
  }
  if (ssid[0] == '\0') {
    Serial.println("provisioning ditolak: SSID kosong");
    return;
  }
  prefs.putString("ssid", ssid);
  prefs.putString("pass", password);
  if (token[0] != '\0') {
    prefs.putString("token", token);
  }
  Serial.println("kredensial tersimpan, perangkat mulai ulang");
  delay(200);
  ESP.restart();
}

void ensureWifi() {
  if (activeSsid[0] == '\0') {
    return;
  }
  if (WiFi.status() == WL_CONNECTED) {
    return;
  }
  if (millis() - lastWifiTryMs < WIFI_RETRY_MS && lastWifiTryMs != 0) {
    return;
  }
  lastWifiTryMs = millis();
  WiFi.mode(WIFI_STA);
  WiFi.begin(activeSsid, activePassword);
#if POWER_SAVE
  esp_wifi_set_ps(WIFI_PS_MAX_MODEM);
#endif
  Serial.println("mencoba Wi-Fi");
}

void ensureTime() {
  if (WiFi.status() != WL_CONNECTED || ntpStarted) {
    return;
  }
  configTime(0, 0, "pool.ntp.org");
  ntpStarted = true;
}

void backfillTimestamps() {
  if (!clockValid()) {
    return;
  }
  const QueuedEvent* event = queue.head();
  if (event == nullptr || event->timeValid) {
    return;
  }
  uint32_t ageMs = millis() - event->capturedMillis;
  time_t when = time(nullptr) - (time_t) (ageMs / 1000);
  char timestamp[21];
  formatTimestamp(timestamp, sizeof(timestamp), when);
  queue.setHeadTimestamp(timestamp);
  persistQueue();
}

int sendBody(HTTPClient& http, const char* body) {
  http.setTimeout(2000);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");
  http.addHeader("Authorization", String("Bearer ") + activeToken);
  http.addHeader("X-Firmware-Version", DEVICE_FIRMWARE);
  return http.POST(reinterpret_cast<uint8_t*>(const_cast<char*>(body)), strlen(body));
}

int postEvent(const QueuedEvent& event) {
  char url[160];
  snprintf(url, sizeof(url), "%s/api/events", API_BASE_URL);
  char body[192];
  snprintf(body, sizeof(body),
           "{\"device_id\":\"%s\",\"event_id\":\"%s\",\"event_type\":\"%s\",\"weight_grams\":%.1f,\"timestamp\":\"%s\"}",
           DEVICE_ID, event.eventId, event.eventType, event.weightGrams, event.timestamp);

  HTTPClient http;
  int code = -1;
  if (strncmp(API_BASE_URL, "https://", 8) == 0) {
    WiFiClientSecure secure;
#if API_TLS_INSECURE
    secure.setInsecure();
#endif
    if (!http.begin(secure, url)) {
      return -1;
    }
    code = sendBody(http, body);
    http.end();
    return code;
  }

  WiFiClient plain;
  if (!http.begin(plain, url)) {
    return -1;
  }
  code = sendBody(http, body);
  http.end();
  return code;
}

bool tokenReady() {
  return activeToken[0] != '\0' && strcmp(activeToken, "REPLACE_WITH_DEVICE_TOKEN") != 0;
}

void uploadHead() {
  if (millis() - lastUploadMs < UPLOAD_INTERVAL_MS) {
    return;
  }
  const QueuedEvent* event = queue.head();
  if (event == nullptr || !event->timeValid || !tokenReady()) {
    return;
  }
  if (WiFi.status() != WL_CONNECTED) {
    return;
  }
  lastUploadMs = millis();
  int code = postEvent(*event);
  if (code == 200 || code == 201) {
    Serial.printf("event terkirim %s\n", event->eventId);
    queue.popHead();
    persistQueue();
    return;
  }
  queue.markHeadAttempt();
  persistQueue();
  Serial.printf("kirim gagal kode=%d event_id=%s percobaan=%u\n", code, event->eventId,
                event->attempts);
}

bool jsonBool(const char* json, const char* key) {
  const char* found = strstr(json, key);
  if (found == nullptr) {
    return false;
  }
  found = strchr(found, ':');
  if (found == nullptr) {
    return false;
  }
  found++;
  while (*found == ' ') {
    found++;
  }
  return *found == 't' || *found == '1';
}

bool jsonFloat(const char* json, const char* key, float* out) {
  const char* found = strstr(json, key);
  if (found == nullptr) {
    return false;
  }
  found = strchr(found, ':');
  if (found == nullptr) {
    return false;
  }
  *out = (float) atof(found + 1);
  return true;
}

void pullConfig() {
  if (!tokenReady() || WiFi.status() != WL_CONNECTED) {
    return;
  }
  if (millis() - lastConfigMs < CONFIG_INTERVAL_MS && lastConfigMs != 0) {
    return;
  }
  lastConfigMs = millis();

  char url[160];
  snprintf(url, sizeof(url), "%s/api/config", API_BASE_URL);
  HTTPClient http;
  WiFiClientSecure secure;
  WiFiClient plain;
  bool begun = false;
  if (strncmp(API_BASE_URL, "https://", 8) == 0) {
#if API_TLS_INSECURE
    secure.setInsecure();
#endif
    begun = http.begin(secure, url);
  } else {
    begun = http.begin(plain, url);
  }
  if (!begun) {
    return;
  }
  http.setTimeout(2000);
  http.addHeader("Accept", "application/json");
  http.addHeader("Authorization", String("Bearer ") + activeToken);
  int code = http.GET();
  bool tareRequested = false;
  if (code == 200) {
    String body = http.getString();
    float threshold = 0;
    float verify = 0;
    if (jsonFloat(body.c_str(), "threshold_grams", &threshold)) {
      detector.setThreshold(threshold);
    }
    if (jsonFloat(body.c_str(), "verify_ms", &verify)) {
      detector.setVerifyMs((uint32_t) verify);
    }
    tareRequested = jsonBool(body.c_str(), "\"tare\"");
  }
  http.end();
  if (tareRequested) {
    performTare();
    reportTare();
  }
}

void reportTare() {
  if (!tokenReady() || WiFi.status() != WL_CONNECTED) {
    Serial.println("tare tersimpan di perangkat, laporan ke server menunggu Wi-Fi");
    return;
  }
  float grams = calibrated && scale.get_scale() != 0 ? scale.get_units(5) : 0;
  char url[160];
  snprintf(url, sizeof(url), "%s/api/tare", API_BASE_URL);
  char body[96];
  snprintf(body, sizeof(body), "{\"device_id\":\"%s\",\"weight_grams\":%.1f}", DEVICE_ID, grams);

  HTTPClient http;
  int code = -1;
  if (strncmp(API_BASE_URL, "https://", 8) == 0) {
    WiFiClientSecure secure;
#if API_TLS_INSECURE
    secure.setInsecure();
#endif
    if (!http.begin(secure, url)) {
      return;
    }
    code = sendBody(http, body);
    http.end();
  } else {
    WiFiClient plain;
    if (!http.begin(plain, url)) {
      return;
    }
    code = sendBody(http, body);
    http.end();
  }
  Serial.printf("laporan tare kode=%d\n", code);
}

void powerSaveDelay() {
  uint32_t elapsed = millis() - lastSampleMs;
  uint32_t wait = elapsed >= SAMPLE_INTERVAL_MS ? 1 : SAMPLE_INTERVAL_MS - elapsed;
#if POWER_SAVE
  if (calibrated && detector.state() == SHELF_NORMAL && queue.size() == 0 && !provisioning) {
    esp_sleep_enable_timer_wakeup((uint64_t) wait * 1000ULL);
    esp_light_sleep_start();
    return;
  }
#endif
  delay(wait > 20 ? 20 : wait);
}

void setup() {
  Serial.begin(115200);
  delay(300);
  prefs.begin("ritelin", false);
  scale.begin(HX711_DOUT_PIN, HX711_SCK_PIN);
  loadCalibration();
  loadIdentity();
  String storedQueue = prefs.getString("queue", "");
  queue.loadFromText(storedQueue.c_str());
  Serial.printf("Rakku %s device_id=%s\n", DEVICE_FIRMWARE, DEVICE_ID);
  if (!calibrated) {
    Serial.println("Belum dikalibrasi. Kosongkan platform, kirim tare, letakkan beban, lalu calibrate <gram>.");
  }
  if (activeSsid[0] == '\0') {
    Serial.println("Wi-Fi kosong. Isi WIFI_SSID atau kirim perintah provision.");
  }
}

void loop() {
  handleSerial();
  serviceProvisioning();
  ensureWifi();
  ensureTime();
  backfillTimestamps();

  if (millis() - lastSampleMs >= SAMPLE_INTERVAL_MS) {
    lastSampleMs = millis();
    sampleScale();
  }

  uploadHead();
  pullConfig();
  powerSaveDelay();
}
