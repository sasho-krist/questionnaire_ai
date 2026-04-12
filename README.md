# Questionnaire AI

Уеб приложение на **Laravel 12** за създаване и провеждане на **тестове/анкети** с множествен избор. Текстовете и въпросите се **генерират с OpenAI (ChatGPT API)**; за всеки въпрос се записват **четири варианта на отговор** и **индекс на верния отговор** (за автоматично точкуване). Има **настройки за точки и времеви лимит**, **страница с резултат** след завършване и преглед на отговорите.

**Допълнително:** регистрация и вход с **ограничаване на опити (throttle)** и логване на неуспешен вход; **забравена/нова парола** (имейл за reset); **публична начална страница**, **ЧЗВ**, **общи условия**, **политика за поверителност**; **банер за бисквитки**; **SEO** (`sitemap.xml`, `robots.txt`, meta/OG); по анкети — **търсене и филтър по статус**, **копие на анкета**, **експорт на резултати като CSV**, **споделяне с линк и QR код** в конструктора (за завършени анкети). За изходяща поща в production е препоръчително **Resend** (HTTPS API), ако SMTP портовете на хостинга са блокирани.

---

## Съдържание

- [Снимки от интерфейса](#снимки-от-интерфейса)
- [Изисквания](#изисквания)
- [Клониране от GitHub](#клониране-от-github)
- [Инсталация](#инсталация)
- [Конфигурация `.env`](#конфигурация-env)
- [Имейл: SMTP и Resend](#имейл-smtp-и-resend)
- [Deploy на сървър](#deploy-на-сървър)
- [База данни и миграции](#база-данни-и-миграции)
- [Стартиране](#стартиране)
- [Потребители и достъп](#потребители-и-достъп)
- [Публични страници и SEO](#публични-страници-и-seo)
- [Как работи приложението](#как-работи-приложението)
- [Генериране на анкета и въпроси](#генериране-на-анкета-и-въпроси)
- [Настройки на теста](#настройки-на-теста)
- [Попълване, време и резултат](#попълване-време-и-резултат)
- [Списък с анкети: филтри, копие, експорт, споделяне](#списък-с-анкети-филтри-копие-експорт-споделяне)
- [Структура на проекта](#структура-на-проекта)
- [SSL / OpenAI под Windows](#ssl--openai-под-windows)
- [Тестове](#тестове)

---

## Снимки от интерфейса

Екранните снимки са в папка **`public/images/`** (`1.png` … `6.png`); в GitHub README се показват с относителни пътища по-долу.

| Файл | Описание (препоръчително съдържание на снимката) |
|------|--------------------------------------------------|
| `1.png` | Начален екран — списък с анкети |
| `2.png` | Форма „Нова анкета“ — заглавие и ключови думи |
| `3.png` | След създаване — успех, зареждане или преход към следващата стъпка |
| `4.png` | Избор между 5 AI-предложени заглавия |
| `5.png` | Конструктор — секции, въпроси, настройки, бутони |
| `6.png` | Попълване на тест или страница с резултат |

![Списък с анкети](public/images/1.png)

![Нова анкета](public/images/2.png)

![След създаване / междинна стъпка](public/images/3.png)

![Избор на заглавие](public/images/4.png)

![Конструктор и настройки](public/images/5.png)

![Тест / резултат](public/images/6.png)

---

## Изисквания

| Компонент | Версия / бележки |
|-----------|-------------------|
| PHP | **8.2+** (Laravel 12; за Laravel 13 е нужен PHP 8.3+) |
| Composer | 2.x (на production задължителен за `vendor/`, вкл. Resend SDK) |
| Разширения PHP | `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `curl` |
| PHP (production) | За `composer` скриптове и част от Artisan: **`proc_open`** не трябва да е в `disable_functions` за CLI (вижте [Deploy на сървър](#deploy-на-сървър)). |
| Node.js (по избор) | за `npm run build` / Vite и Tailwind в assets |
| OpenAI | API ключ с достъп до избрания модел (по подразбиране `gpt-4o-mini`) |
| Имейл (production) | Препоръчително **Resend** (`MAIL_MAILER=resend`) при блокиран SMTP; вижте [Имейл](#имейл-smtp-и-resend). |

---

## Клониране от GitHub

```bash
git clone git@github.com:sasho-krist/questionnaire_ai.git
cd questionnaire_ai
```

С **HTTPS**:

```bash
git clone https://github.com/sasho-krist/questionnaire_ai.git
cd questionnaire_ai
```

---

## Инсталация

### 1. Зависимости на PHP

```bash
composer install
```

### 2. Конфигурация на средата

```bash
copy .env.example .env
# Linux/macOS: cp .env.example .env
```

Редактирайте `.env` (вижте [Конфигурация `.env`](#конфигурация-env)).

### 3. Ключ на приложението

```bash
php artisan key:generate
```

### 4. База данни

Вижте следващата секция — SQLite или MySQL.

### 5. Миграции

```bash
php artisan migrate
```

### 6. Frontend (по избор)

```bash
npm install
npm run build
# или за разработка: npm run dev
```

Приложението работи и **само с Bootstrap от CDN** в Blade изгледите; Vite не е задължителен за основните страници.

---

## Конфигурация `.env`

### Задължително за AI

| Променлива | Описание |
|------------|----------|
| `AI_API_PUBLIC_KEY` | **Таен** API ключ от [OpenAI Platform](https://platform.openai.com/api-keys) (името в проекта е историческо; не е „публичен“ ключ). |
| `OPENAI_MODEL` | Модел за чат (по подразбиране `gpt-4o-mini`). |
| `OPENAI_VERIFY_SSL` | На Windows/WAMP при грешка `cURL error 60` сложете `false` само за локална разработка; в production използвайте `true` и настройте `curl.cainfo` в `php.ini`. |

### Приложение и URL

| Променлива | Пример |
|------------|--------|
| `APP_NAME` | `Questionnaire AI` |
| `APP_URL` | `http://localhost:8000` или вашият виртуален хост |

### База данни

**SQLite (по подразбиране в `.env.example`):**

```env
DB_CONNECTION=sqlite
```

Файлът `database/database.sqlite` се създава при първа миграция, ако липсва.

**MySQL:**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=questionnaire_ai
DB_USERNAME=root
DB_PASSWORD=
```

Създайте базата предварително (напр. `CREATE DATABASE questionnaire_ai ...`). В `AppServiceProvider` е зададено `Schema::defaultStringLength(191)` за съвместимост с utf8mb4 и индекси в MySQL.

### Сесии и опашки (Laravel по подразбиране)

За `SESSION_DRIVER=database` и `QUEUE_CONNECTION=database` са нужни миграциите на Laravel (включени в `php artisan migrate`).

---

## Имейл: SMTP и Resend

За **връзка за нова парола** (забравена парола) приложението изпраща транзакционни писма. Локално е удобно `MAIL_MAILER=log` (писмата се записват в лога).

**Production:** много хостинги **блокират изходящи SMTP портове** (587/465). Тогава използвайте **Resend** (HTTPS API, без SMTP портове). В проекта са добавени пакетите `resend/resend-laravel` и `resend/resend-php`.

| Променлива | Бележка |
|------------|---------|
| `MAIL_MAILER` | `log` (локално), `smtp` (ако хостингът позволява), **`resend`** (препоръчително при блокиран SMTP). |
| `RESEND_API_KEY` | Ключ от [Resend](https://resend.com) → **API Keys** (започва с `re_`). |
| `MAIL_FROM_ADDRESS` | **Задължително** при Resend: верифициран адрес от домейн в Resend или за тест `onboarding@resend.dev`. |
| `MAIL_FROM_NAME` | Име на подател (напр. `${APP_NAME}`). |
| SMTP при `MAIL_MAILER=smtp` | `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION` (`tls` / `ssl`). |

След всяка промяна на `.env` на сървъра:

```bash
php artisan config:clear
php artisan cache:clear
```

Ако сте ползвали `php artisan config:cache`, изтрийте кеша преди да тествате нова поща — иначе Laravel може да продължи да ползва стар `MAIL_MAILER`.

---

## Deploy на сървър

1. **Composer** — в корена на проекта (където е `composer.json`):

   ```bash
   composer install --no-dev -o
   ```

   Ако командата `composer` липсва в SSH, инсталирайте [Composer](https://getcomposer.org/download/) локално като `composer.phar` и ползвайте `php composer.phar install --no-dev -o`.

2. **`proc_open`:** ако Composer спре с грешка за **`proc_open`** при `php artisan package:discover`, първо:

   ```bash
   composer install --no-dev -o --no-scripts
   ```

   След това активирайте **`proc_open`** (махнете го от `disable_functions` в **CLI** `php.ini`, вижте `php --ini`) или поискайте това от хостинга. Накрая ръчно:

   ```bash
   php artisan package:discover --ansi
   ```

3. **Миграции:** `php artisan migrate --force` (production).

4. **Кеш:** след обновяване на код или `.env` — `php artisan config:clear` (и при нужда `optimize:clear`).

5. **Подпапка:** ако приложението е под URL като `https://example.com/anketi`, задайте **`APP_URL`** с пълния път до публичната папка (напр. `https://example.com/anketi`) и настройте уеб сървъра да сочи към **`public/`**.

---

## База данни и миграции

Основни таблици:

- `questionnaires` — анкета, статус, предложени заглавия, избрано заглавие, **`points_per_correct`**, **`seconds_per_question`**
- `questionnaire_sections` — секции
- `questionnaire_questions` — текст на въпрос, **`choice_options`** (JSON, 4 стринга), **`correct_option`** (0–3)
- `questionnaire_attempts` — опит за попълване, отговори (JSON), **`started_at`**, **`deadline_at`**, **`score`**, **`max_score`**, **`completed_at`**

Пълен reset на базата (изтрива данни):

```bash
php artisan migrate:fresh
```

Има миграция за **попълване на `email_verified_at`** за по-стари инсталации (по избор). Новите регистрации записват дата в това поле автоматично.

---

## Стартиране

Вграден PHP сървър:

```bash
php artisan serve
```

Отворете `APP_URL` или `http://127.0.0.1:8000`.

Под **Apache** (WAMP) насочете **DocumentRoot** към папката **`public/`**.

---

## Потребители и достъп

- **Регистрация** (`/register`) — след успех влизате веднага в списъка с анкети. **Вход** (`/login`). При вход има **rate limit** (throttle) срещу brute force; неуспешните опити се **логват**.
- **Забравена парола:** `/forgot-password` и страница за нова парола с токен от имейл.
- **Изход** — пренасочване към началната страница.

За reset на парола вижте [Имейл: SMTP и Resend](#имейл-smtp-и-resend).

---

## Публични страници и SEO

| Маршрут | Описание |
|---------|----------|
| `/` | За гости: **лендинг**; за влезли потребители: пренасочване към списъка с анкети. |
| `/faq`, `/terms`, `/privacy` | ЧЗВ, общи условия, поверителност. |
| `/sitemap.xml`, `/robots.txt` | Карта на сайта и robots за търсачки. |

В оформлението има **банер за бисквитки** (локално съгласие в `localStorage`). Meta заглавие/описание и **Open Graph** използват настройки от `config/seo.php` (вкл. подразбирано OG изображение, напр. под `public/images/`).

---

## Как работи приложението

1. **Регистрация** — създавате акаунт и продължавате към анкетите.
2. **Нова анкета** — въвеждате работно заглавие и ключови думи; AI генерира **5 заглавия**.
3. **Избор на заглавие** — избирате едно; AI създава **4 секции × 4 въпроса** (по 4 варианта на отговор); след допълнителна AI стъпка се записва **верният индекс** за всяко попълнение.
4. **Конструктор** — преглед на секции и въпроси; **настройки на теста**; „Още въпроси“ по секция; **Завърши генерирането** — анкетата става достъпна за стартиране.
5. **Старт** — нов **опит** с уникален URL `/play/{uuid}`.
6. **Попълване** — множествен избор; при зададен лимит — общ таймер; при изтичане — визуално оцветяване и блокиране на промени (при submit отговорите пак се изпращат).
7. **Завърши теста** — запис на резултат и пренасочване към **страница с резултат** и преглед на отговорите.

Идентификация на анкетата в URL е по **`uuid`**, не по числов `id`.

---

## Генериране на анкета и въпроси

| Стъпка | Маршрут / действие |
|--------|-------------------|
| Форма | `GET /questionnaires/create` → **Нова анкета** |
| Генериране на 5 заглавия | `POST /questionnaires` — извиква OpenAI, записва `title_suggestions` |
| Избор на заглавие | `GET/POST .../titles` — избор + генериране на **16 въпроса** в **4 секции** |
| Още въпроси | `POST .../generate-more` — още **4 въпроса** към избрана секция |
| Завършване на черновата | `POST .../finish` — статус `completed` |

Всички генерирани въпроси с множествен избор имат **точно 4 опции** и записан **`correct_option`** за точкуване.

---

## Настройки на теста

В **конструктора** (`GET /questionnaires/{uuid}/build`) има карта **„Настройки на теста“**:

| Настройка | Описание |
|-----------|----------|
| **Точки за верен отговор** | Колко точки се добавят за всеки верен множествен избор (напр. `1.00`). |
| **Секунди на въпрос (лимит)** | Ако е попълнено: общ лимит = **брой всички въпроси × секунди** (от старт на опита). Празно = без лимит. |

Запис: `POST /questionnaires/{uuid}/settings`.

Настройките важат за **нови опити** след промяна; вече завършени опити не се преизчисляват автоматично.

---

## Попълване, време и резултат

| Действие | Маршрут |
|----------|---------|
| Старт на нов опит | `GET /questionnaires/{uuid}/play/start` |
| Попълване | `GET /play/{attemptUuid}` — форма с радио бутони |
| Запазване | `POST /play/{attemptUuid}` — `mark_complete=0` запазва, `mark_complete=1` завършва |
| Резултат | `GET /play/{attemptUuid}/results` — точки, верни/грешни, вашият избор |

Точкуването сравнява избрания индекс (0–3) с **`correct_option`**. Въпроси без зададен верен отговор (стари данни) не участват в максималния резултат.

---

## Списък с анкети: филтри, копие, експорт, споделяне

На **`GET /questionnaires`**:

| Функция | Описание |
|---------|----------|
| **Търсене / филтър** | Query параметри `q` (текст) и `status` (чернова, избор на заглавие, в изграждане, завършена). |
| **Копие** | За собственика: дублира анкетата със секции и въпроси (`POST .../duplicate`). |
| **Резултати** | Преглед на опити по участници; **експорт CSV** (`GET .../export-results`) с BOM за Excel. |

В **конструктора** за **завършена** анкета има **публичен линк за старт** (копиране) и **QR код** към същия адрес (външна услуга за генериране на изображение). Достъпът до старт/попълване изисква **влезъл потребител** (маршрутите са под `auth`).

---

## Структура на проекта (важни файлове)

```
app/
  Http/Controllers/
    Auth/                          # Login, Register, Forgot/Reset password
    PageController.php             # начало, terms, faq, privacy
    SeoController.php              # sitemap.xml, robots.txt
    QuestionnaireController.php    # CRUD, филтри, duplicate, export, настройки, генериране
    QuestionnairePlayController.php # опити, отговори, резултат
  Support/
    ResendInstallChecker.php      # подсказки при липсващ Resend SDK на сървъра
  Models/
    User.php
    Questionnaire.php
    QuestionnaireSection.php
    QuestionnaireQuestion.php
    QuestionnaireAttempt.php
  Services/
    OpenAiService.php              # OpenAI + логване при грешки
    AttemptScoringService.php      # изчисляване на score / max_score
  Providers/AppServiceProvider.php # rate limiters за login / имейл
config/services.php               # OpenAI, Resend key
config/mail.php
config/seo.php
routes/web.php
resources/views/
  layouts/app.blade.php
  pages/landing.blade.php, faq, terms, …
  auth/*.blade.php
  components/cookie-banner.blade.php
  questionnaires/*.blade.php
database/migrations/
```

---

## SSL / OpenAI под Windows

При **`cURL error 60`** (SSL certificate problem):

1. В `.env`: `OPENAI_VERIFY_SSL=false` само локално, **или**
2. В `php.ini`: `curl.cainfo` и `openssl.cafile` към актуален `cacert.pem`.

`config/services.php` чете `OPENAI_VERIFY_SSL` и при `false` задава `verify => false` на HTTP клиента към OpenAI.

При грешки от API (мрежа, квоти, невалиден отговор) грешките се **логват**, а към потребителя се показва обобщено съобщение.

---

## Тестове

```bash
php artisan test
```

---

## Лиценз

Проектът следва лиценза на Laravel skeleton (**MIT**), освен ако не е указано друго.

---

## Автор и репозиторий

- **GitHub:** [sasho-krist/questionnaire_ai](https://github.com/sasho-krist/questionnaire_ai)

При проблеми с **push/SSH**: вижте `~/.ssh/config` без **UTF-8 BOM** (препоръчително записване с UTF-8 без BOM) и правилния `IdentityFile` за акаунта, който притежава хранилището.
