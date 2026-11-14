# Makefile project legacy (Laragon/WSL, bukan Docker). Target pemeriksaan identitas servis (no_service + kd_cabang).
# PHP: php di PATH, atau Laragon via WSL (override: make PHP_BIN=/path/php lint)
PHP_BIN ?= $(shell command -v php 2>/dev/null || ls /mnt/c/laragon/bin/php/php-*/php.exe 2>/dev/null | tail -1)
ifneq (,$(findstring .exe,$(PHP_BIN)))
  PHP_ARGS = -d extension_dir="$(shell wslpath -w $(dir $(PHP_BIN)))\ext" -d extension=php_mysqli.dll
  AUDIT = $(shell wslpath -w $(CURDIR)/scripts/audit_no_service.php)
else
  PHP_ARGS = -d extension=mysqli
  AUDIT = $(CURDIR)/scripts/audit_no_service.php
endif

.PHONY: lint audit-no-service monitor-no-service install-hooks

# Gagal bila ada query servis yang join/UPDATE/DELETE hanya dengan no_service.
lint: audit-no-service

audit-no-service:
	$(PHP_BIN) $(PHP_ARGS) -f "$(AUDIT)" -- --kode

# Cek harian: baris anak tanpa kd_cabang tak terlacak karantina, view tanpa kd_cabang, duplikat induk.
monitor-no-service:
	$(PHP_BIN) $(PHP_ARGS) -f "$(AUDIT)" -- --monitor

install-hooks:
	cp scripts/git-hooks/pre-commit .git/hooks/pre-commit
	chmod +x .git/hooks/pre-commit
	@echo "Hook pre-commit terpasang."
