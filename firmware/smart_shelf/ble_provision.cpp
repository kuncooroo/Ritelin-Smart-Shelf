#include "ble_provision.h"

#if defined(ARDUINO_ARCH_ESP32)

#include <Arduino.h>
#include <BLEDevice.h>
#include <BLEServer.h>
#include <BLEUtils.h>

namespace {

constexpr char kServiceUuid[] = "4f1a0001-7c3b-4a6e-9d11-6b2e8a100001";
constexpr char kSsidUuid[] = "4f1a0002-7c3b-4a6e-9d11-6b2e8a100001";
constexpr char kPasswordUuid[] = "4f1a0003-7c3b-4a6e-9d11-6b2e8a100001";
constexpr char kTokenUuid[] = "4f1a0004-7c3b-4a6e-9d11-6b2e8a100001";
constexpr char kCommitUuid[] = "4f1a0005-7c3b-4a6e-9d11-6b2e8a100001";

char gSsid[33];
char gPassword[65];
char gToken[81];
volatile bool gCommit = false;
bool gStarted = false;

void copyValue(BLECharacteristic* characteristic, char* dest, size_t size) {
  String value = characteristic->getValue();
  strncpy(dest, value.c_str(), size - 1);
  dest[size - 1] = '\0';
}

#if defined(ESP_ARDUINO_VERSION_MAJOR) && ESP_ARDUINO_VERSION_MAJOR >= 3
#define RITELIN_ON_WRITE void onWrite(BLECharacteristic* characteristic, esp_ble_gatts_cb_param_t* param) override
#else
#define RITELIN_ON_WRITE void onWrite(BLECharacteristic* characteristic) override
#endif

class TextCallbacks : public BLECharacteristicCallbacks {
 public:
  explicit TextCallbacks(char* dest, size_t size) : dest_(dest), size_(size) {}

  RITELIN_ON_WRITE { copyValue(characteristic, dest_, size_); }

 private:
  char* dest_;
  size_t size_;
};

class CommitCallbacks : public BLECharacteristicCallbacks {
  RITELIN_ON_WRITE {
    String value = characteristic->getValue();
    if (value == "1") {
      gCommit = true;
    }
  }
};

TextCallbacks ssidCallbacks(gSsid, sizeof(gSsid));
TextCallbacks passwordCallbacks(gPassword, sizeof(gPassword));
TextCallbacks tokenCallbacks(gToken, sizeof(gToken));
CommitCallbacks commitCallbacks;

}  // namespace

void bleProvisionStart(const char* deviceName) {
  if (gStarted) {
    return;
  }
  memset(gSsid, 0, sizeof(gSsid));
  memset(gPassword, 0, sizeof(gPassword));
  memset(gToken, 0, sizeof(gToken));
  gCommit = false;

  char advertised[32];
  snprintf(advertised, sizeof(advertised), "RITELIN-%s", deviceName);
  BLEDevice::init(advertised);
  BLEServer* server = BLEDevice::createServer();
  BLEService* service = server->createService(kServiceUuid);

  BLECharacteristic* ssid = service->createCharacteristic(
      kSsidUuid, BLECharacteristic::PROPERTY_WRITE);
  BLECharacteristic* password = service->createCharacteristic(
      kPasswordUuid, BLECharacteristic::PROPERTY_WRITE);
  BLECharacteristic* token = service->createCharacteristic(
      kTokenUuid, BLECharacteristic::PROPERTY_WRITE);
  BLECharacteristic* commit = service->createCharacteristic(
      kCommitUuid, BLECharacteristic::PROPERTY_WRITE);
  ssid->setCallbacks(&ssidCallbacks);
  password->setCallbacks(&passwordCallbacks);
  token->setCallbacks(&tokenCallbacks);
  commit->setCallbacks(&commitCallbacks);

  service->start();
  BLEAdvertising* advertising = BLEDevice::getAdvertising();
  advertising->addServiceUUID(kServiceUuid);
  advertising->start();
  gStarted = true;
}

bool bleProvisionTake(char* ssid, size_t ssidLen, char* password, size_t passwordLen,
                      char* token, size_t tokenLen) {
  if (!gCommit) {
    return false;
  }
  strncpy(ssid, gSsid, ssidLen - 1);
  ssid[ssidLen - 1] = '\0';
  strncpy(password, gPassword, passwordLen - 1);
  password[passwordLen - 1] = '\0';
  strncpy(token, gToken, tokenLen - 1);
  token[tokenLen - 1] = '\0';
  gCommit = false;
  return true;
}

#else

void bleProvisionStart(const char* deviceName) { (void) deviceName; }

bool bleProvisionTake(char* ssid, size_t ssidLen, char* password, size_t passwordLen,
                      char* token, size_t tokenLen) {
  (void) ssid;
  (void) ssidLen;
  (void) password;
  (void) passwordLen;
  (void) token;
  (void) tokenLen;
  return false;
}

#endif
