#pragma once

#include <stddef.h>
#include <stdbool.h>

void bleProvisionStart(const char* deviceName);
bool bleProvisionTake(char* ssid, size_t ssidLen, char* password, size_t passwordLen,
                      char* token, size_t tokenLen);
