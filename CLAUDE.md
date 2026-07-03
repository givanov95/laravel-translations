# laravel-translations — multi-locale translation инфраструктура за Laravel + Inertia + Vue

PHP 8.3 Composer library (Laravel 11–13), без frontend build (само `TranslationPlugin.ts` като publish-ван asset). Комуникация с потребителя: български. Код, commit-и и PR-и: английски.

Работният флоу (issue-та, PR-и) идва от плъгина `gws@claude-flow` — `/gws:issue <N>`. Този файл носи само спецификите на проекта.

## Branch-ове
- Базов branch: `main`. Issue branch-ове: `fix|feat|chore/N-kratko-ime` от него, PR към него, squash merge.
- Issue-то се затваря с `Fixes #N` в тялото на commit-а (базовият branch е default — затваря се при merge на PR-а).

## Deploy
- Няма — проектът не се качва на сървър. `/gws:ship` не е приложим тук; доставката е merge в базовия branch.

## Build и commit-и
- Няма build стъпка. Тестове: `composer test` (PHPUnit + Orchestra Testbench). Статичен анализ: `composer analyse` (Larastan).
- Pre-commit hook от `givanov95/laravel-git-hooks` (symlink към `pre-commit` в root-а) пуска проверки при commit.
- Commit стил: Conventional Commits на английски (`fix(scope): ...`).

## GitHub
- Нови issue-та се добавят в project board „gws".
