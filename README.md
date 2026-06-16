# СиЭК — сайт компании

Статический сайт на базе Tilda с PHP-обработчиками для двух опросных листов.

## Что есть в проекте

- Главная страница: `index.html` и `index.htm`
- Контакты: `contacts.html`
- Каталог:
  - `catalog1*.html` — тягодутьевые машины
  - `catalog2*.html` — РОУ / ОУ / РУ
- Формы:
  - `OprostnaiList.php` — опросный лист ТДМ
  - `OprostnaiList2.php` — опросный лист РОУ
- Отправка почты:
  - `send.php`
  - `send2.php`
- Почтовая логика и SMTP:
  - `mail_helper.php`
- Генерация Word-документов:
  - `docx_helper.php`
  - `templates/tdm-template.docx`
  - `templates/rou-template.docx`

## Локальный запуск

Нужен PHP 8+.

```bash
cd /Users/danlluxx/siec-web
php -S 127.0.0.1:8000
```

После запуска:

- Главная: `http://127.0.0.1:8000/index.html`
- Опросник ТДМ: `http://127.0.0.1:8000/OprostnaiList.php`
- Опросник РОУ: `http://127.0.0.1:8000/OprostnaiList2.php`

## Настройка почты

Проект читает настройки из файла `.env` в корне сайта.

Пример:

```env
MAIL_TO_ADDRESS=your-target@example.com
MAIL_FROM_ADDRESS=example@mail.ru
MAIL_FROM_NAME="Сибирская энергетическая компания"
MAIL_HOST=smtp.mail.ru
MAIL_PORT=465
MAIL_USERNAME=example@mail.ru
MAIL_PASSWORD=app_password_here
MAIL_ENCRYPTION=ssl
MAIL_SMTP_AUTH=true
```

Шаблон лежит в `.env.example`.

Важно:

- для `mail.ru` нужен пароль для внешнего приложения
- `.env` не нужно коммитить в Git
- если SMTP не настроен, письма могут не доходить

## Как работают формы

1. Пользователь заполняет форму.
2. Данные отправляются в `send.php` или `send2.php`.
3. PHP собирает заполненный `.docx` по шаблону.
4. Письмо уходит через `PHPMailer` с вложенным Word-файлом.
5. После успеха пользователь попадает на `success.html`.

## Полезные файлы

- `js/siec-animation-speedup.js` — ускорение и сглаживание анимаций, правки галереи
- `mail_debug.log` — лог отправки писем
- `images/siec-logo.png` — логотип для опросников

## Деплой

Для выкладки на хостинг нужно загрузить проект целиком, включая:

- PHP-файлы
- папку `PHPMailer`
- папку `templates`
- `.env`

Если после выкладки форма открывается, но письма нет, сначала проверьте:

- корректность SMTP в `.env`
- `mail_debug.log`
- папку "Спам"
