# Monorepo shortcuts. See docs/SETUP.md and docs/CONTRIBUTING.md.
.PHONY: verify-all check check-backend check-flutter backend-fix browser-smoke

check:            ## Full milestone gate (backend + Flutter)
	scripts/check.sh all

check-backend:    ## Pint, PHPStan, parallel tests, Vite build
	scripts/check.sh backend

check-flutter:    ## dart format, flutter analyze, flutter test in every app/package
	scripts/check.sh flutter

backend-fix:      ## Auto-fix backend code style
	cd backend && vendor/bin/pint

browser-smoke:    ## Real-browser smoke check of the admin login form (headless Chrome, testing DB)
	scripts/browser-smoke.sh

verify-all:       ## Everything CI runs, summary in verify-results/SUMMARY.md (docs/RELEASE_REPORT.md)
	scripts/verify-all.sh
