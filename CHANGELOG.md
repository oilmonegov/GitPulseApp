# Changelog

## 1.0.0 (2026-01-19)


### Features

* **branding:** add GitPulse icon with pulse line and commit dots ([545d25e](https://github.com/oilmonegov/GitPulseApp/commit/545d25e066825717f9ed81ce83175016540c66eb))
* **dashboard:** implement Sprint 5 analytics dashboard ([#2](https://github.com/oilmonegov/GitPulseApp/issues/2)) ([d1f3378](https://github.com/oilmonegov/GitPulseApp/commit/d1f337827040e5d30fb39b2be20f1e6d7b070d61))
* development lifecycle quality gates + enhanced user settings hub ([#5](https://github.com/oilmonegov/GitPulseApp/issues/5)) ([850010f](https://github.com/oilmonegov/GitPulseApp/commit/850010f261c5a26cf5075620cf5c9905c652068c))
* **dx:** add lessons learned reminder hook and /lessons skill ([#3](https://github.com/oilmonegov/GitPulseApp/issues/3)) ([fd7f2a9](https://github.com/oilmonegov/GitPulseApp/commit/fd7f2a957b48a3a9ba4e8c85e18ba0d1e6d5f020))
* **infra:** add enterprise-grade infrastructure (Tiers 1-3) ([#17](https://github.com/oilmonegov/GitPulseApp/issues/17)) ([4c929d1](https://github.com/oilmonegov/GitPulseApp/commit/4c929d1e6e6c115c5dc902d2bdc72401b69d9910))
* **sprint1:** complete infrastructure with CQRS, webhooks, and testing ([616d07e](https://github.com/oilmonegov/GitPulseApp/commit/616d07e059e120184961fac47a76b729b25c38e2))
* **sprint2:** add Repository and Commit models with factories ([aebddba](https://github.com/oilmonegov/GitPulseApp/commit/aebddba35d0414dfed63f0800f2fd2f6c342c4c5))
* **sprint4:** add Commit Documentation Engine with parsing and impact scoring ([cb30581](https://github.com/oilmonegov/GitPulseApp/commit/cb305817c0a584967c50e996824d49250e3eab10))
* **sprint4:** add push event processing with commit storage ([50b958a](https://github.com/oilmonegov/GitPulseApp/commit/50b958a2736e614286481bdbe8f52fe2d046164b))


### Bug Fixes

* **charts:** remove duplicate hsl() wrapper in useChartColors ([bbe46ec](https://github.com/oilmonegov/GitPulseApp/commit/bbe46ec07efb39cc94812424f4891878cf8a262f))
* **charts:** remove duplicate hsl() wrapper in useChartColors ([391fbdb](https://github.com/oilmonegov/GitPulseApp/commit/391fbdbc9a29a15b04e5666ff35c0f48abedf321))
* **ci:** add --with-form flag to wayfinder generate ([6dafe7a](https://github.com/oilmonegov/GitPulseApp/commit/6dafe7a4162c98ced164eaaeaf115e22efe630e7))
* **ci:** generate Wayfinder types before frontend type-check ([cf50f5b](https://github.com/oilmonegov/GitPulseApp/commit/cf50f5b8c07e71e209f7bbf1f771dde11af184bd))
* **dashboard:** resolve Chart.js dark mode color issues ([#4](https://github.com/oilmonegov/GitPulseApp/issues/4)) ([97b5a86](https://github.com/oilmonegov/GitPulseApp/commit/97b5a86e7f7ed1d0fec3cfe4e6ae207d8a9294a2))
* resolve PHPStan static analysis errors ([9e4b274](https://github.com/oilmonegov/GitPulseApp/commit/9e4b2741ee9afb7238219b8d733287594a7eb21d))
* **settings:** improve Appearance and DeleteUser component styling ([#16](https://github.com/oilmonegov/GitPulseApp/issues/16)) ([3e2bb60](https://github.com/oilmonegov/GitPulseApp/commit/3e2bb60798762f925dc92550f788e721a13d52b5))
* **ui:** use semantic CSS variables for light mode consistency ([4c929d1](https://github.com/oilmonegov/GitPulseApp/commit/4c929d1e6e6c115c5dc902d2bdc72401b69d9910))


### Performance Improvements

* **build:** add code splitting for vendor chunks ([2b2707b](https://github.com/oilmonegov/GitPulseApp/commit/2b2707b459e8ce3f33fc3473251d5b9b635df593))


### Miscellaneous Chores

* add PHPStan to pre-push hook ([57801b2](https://github.com/oilmonegov/GitPulseApp/commit/57801b24f49edef4057da371901bef65a2f6a5b8))
* **ci:** Bump actions/cache from 4 to 5 ([#8](https://github.com/oilmonegov/GitPulseApp/issues/8)) ([d70663a](https://github.com/oilmonegov/GitPulseApp/commit/d70663a7e23b91e27fabe0256b4dd2350d61dc7a))
* **ci:** Bump actions/checkout from 4 to 6 ([#7](https://github.com/oilmonegov/GitPulseApp/issues/7)) ([e364b04](https://github.com/oilmonegov/GitPulseApp/commit/e364b04dcb22f24aa6f90758d36158efcf4213d3))
* **ci:** Bump actions/setup-node from 4 to 6 ([#6](https://github.com/oilmonegov/GitPulseApp/issues/6)) ([e8b6b9b](https://github.com/oilmonegov/GitPulseApp/commit/e8b6b9b7c8d6619fd62ea52aeee5d1d7697ceda7))
* **config:** update APP_NAME in .env.example to GitPulseApp ([6f70eb1](https://github.com/oilmonegov/GitPulseApp/commit/6f70eb165a30a21d5f25b3c121dcaa440ff2700b))
* **config:** update APP_NAME in .env.example to GitPulseApp ([babec05](https://github.com/oilmonegov/GitPulseApp/commit/babec05bfc1a2d694fc2ff6d5a08ede76813dcce))
* **deps:** Bump @rollup/rollup-linux-x64-gnu from 4.55.1 to 4.55.2 ([#19](https://github.com/oilmonegov/GitPulseApp/issues/19)) ([f9ecd43](https://github.com/oilmonegov/GitPulseApp/commit/f9ecd43346e4bcb5e852d5d6f5ee2e560fecc086))
* **deps:** Bump @rollup/rollup-linux-x64-gnu from 4.9.5 to 4.55.1 ([#12](https://github.com/oilmonegov/GitPulseApp/issues/12)) ([cf0dee3](https://github.com/oilmonegov/GitPulseApp/commit/cf0dee336ec385c105882a1b3f0a65cfc99ea9e0))
* **deps:** Bump @rollup/rollup-win32-x64-msvc from 4.9.5 to 4.55.1 ([#10](https://github.com/oilmonegov/GitPulseApp/issues/10)) ([e47d157](https://github.com/oilmonegov/GitPulseApp/commit/e47d1578fca3599f8755139e675fec0fdad3f911))
* **deps:** Bump @vueuse/core from 12.8.2 to 14.1.0 ([#14](https://github.com/oilmonegov/GitPulseApp/issues/14)) ([0162c4f](https://github.com/oilmonegov/GitPulseApp/commit/0162c4f17bb04ac4e2cef58c83949a6792de3b47))
* **deps:** Bump the dev-tooling group with 3 updates ([#9](https://github.com/oilmonegov/GitPulseApp/issues/9)) ([12dea92](https://github.com/oilmonegov/GitPulseApp/commit/12dea92069031db5161f05f42af26efe7872b140))
* **deps:** Bump vue from 3.5.26 to 3.5.27 in the vue group ([#18](https://github.com/oilmonegov/GitPulseApp/issues/18)) ([9d20f09](https://github.com/oilmonegov/GitPulseApp/commit/9d20f09f0e274583e1fb2929e41f969a9e9c7aa8))
* **deps:** Bump vue-tsc from 2.2.12 to 3.2.2 ([#13](https://github.com/oilmonegov/GitPulseApp/issues/13)) ([884c2e6](https://github.com/oilmonegov/GitPulseApp/commit/884c2e69fa411d1a059af43f1e90f24c74b10065))
