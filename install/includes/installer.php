<?php
/**
 * TONBANKCARD automatic hosting installer helpers.
 */

if ( ! function_exists( 'tonbankcard_installer_root_dir' ) ) {
    /**
     * Returns the repository root for installer operations.
     *
     * @return string
     */
    function tonbankcard_installer_root_dir() {
        return dirname( __DIR__, 2 );
    }
}

if ( ! function_exists( 'tonbankcard_installer_env_example_path' ) ) {
    /**
     * @param string|null $root
     * @return string
     */
    function tonbankcard_installer_env_example_path( $root = null ) {
        $root = null === $root ? tonbankcard_installer_root_dir() : rtrim( (string) $root, '/' );
        return $root . '/.env.example';
    }
}

if ( ! function_exists( 'tonbankcard_installer_env_path' ) ) {
    /**
     * @param string|null $root
     * @return string
     */
    function tonbankcard_installer_env_path( $root = null ) {
        $root = null === $root ? tonbankcard_installer_root_dir() : rtrim( (string) $root, '/' );
        return $root . '/.env';
    }
}

if ( ! function_exists( 'tonbankcard_installer_unquote_env_value' ) ) {
    /**
     * @param string $value
     * @return string
     */
    function tonbankcard_installer_unquote_env_value( string $value ) {
        $value = trim( $value );
        if ( '' === $value ) {
            return '';
        }

        $first = substr( $value, 0, 1 );
        $last  = substr( $value, -1 );
        if ( ( '"' === $first && '"' === $last ) || ( "'" === $first && "'" === $last ) ) {
            $value = substr( $value, 1, -1 );
        }

        return str_replace( [ '\\"', '\\\\' ], [ '"', '\\' ], $value );
    }
}

if ( ! function_exists( 'tonbankcard_installer_parse_env_file' ) ) {
    /**
     * Parses simple KEY=VALUE dotenv files.
     *
     * @param string $path
     * @return array
     */
    function tonbankcard_installer_parse_env_file( string $path ) {
        if ( ! is_readable( $path ) ) {
            return [];
        }

        $lines = file( $path, FILE_IGNORE_NEW_LINES );
        if ( ! is_array( $lines ) ) {
            return [];
        }

        $values = [];
        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( '' === $line || '#' === $line[0] ) {
                continue;
            }

            if ( 0 === strpos( $line, 'export ' ) ) {
                $line = trim( substr( $line, 7 ) );
            }

            $separator = strpos( $line, '=' );
            if ( FALSE === $separator ) {
                continue;
            }

            $key = trim( substr( $line, 0, $separator ) );
            if ( '' === $key ) {
                continue;
            }

            $values[ $key ] = tonbankcard_installer_unquote_env_value( substr( $line, $separator + 1 ) );
        }

        return $values;
    }
}

if ( ! function_exists( 'tonbankcard_installer_supported_languages' ) ) {
    /**
     * @return array
     */
    function tonbankcard_installer_supported_languages() {
        return [
            'en' => 'English',
            'ru' => 'Русский',
        ];
    }
}

if ( ! function_exists( 'tonbankcard_installer_normalize_language' ) ) {
    /**
     * @param mixed $language
     * @return string
     */
    function tonbankcard_installer_normalize_language( $language ) {
        $language = strtolower( trim( (string) $language ) );
        if ( FALSE !== strpos( $language, '-' ) ) {
            $language = substr( $language, 0, strpos( $language, '-' ) );
        }

        $supported = tonbankcard_installer_supported_languages();
        return isset( $supported[ $language ] ) ? $language : 'en';
    }
}

if ( ! function_exists( 'tonbankcard_installer_translations' ) ) {
    /**
     * @return array
     */
    function tonbankcard_installer_translations() {
        return [
            'ru' => [
                'TONBANKCARD Automatic Hosting Installer' => 'Автоматический установщик TONBANKCARD для хостинга',
                'Configure PHP hosting, MySQL or MariaDB, Telegram Mini App settings, providers, feature flags, worker tokens, and database migrations from one guarded setup screen.' => 'Настройте PHP-хостинг, MySQL или MariaDB, параметры Telegram Mini App, провайдеры, флаги функций, токены воркеров и миграции базы данных на одном защищенном экране.',
                'Installer language' => 'Язык установщика',
                'Apply language' => 'Применить язык',
                'Installer steps' => 'Шаги установщика',
                'Step 1. Readiness' => 'Шаг 1. Готовность',
                'Step 8. Migrations' => 'Шаг 8. Миграции',
                'Installer locked' => 'Установщик заблокирован',
                'To reopen it, edit .env, set TONBANKCARD_INSTALLER_ENABLED=true, set a strong TONBANKCARD_INSTALLER_TOKEN, and open /install/?token=your-token.' => 'Чтобы открыть его снова, отредактируйте .env, задайте TONBANKCARD_INSTALLER_ENABLED=true, укажите надежный TONBANKCARD_INSTALLER_TOKEN и откройте /install/?token=your-token.',
                'Installer messages' => 'Сообщения установщика',
                'Confirm the host can run TONBANKCARD before writing configuration or applying migrations.' => 'Проверьте, что хостинг может запускать TONBANKCARD, прежде чем записывать конфигурацию или применять миграции.',
                'Step 8. Database migrations and final write' => 'Шаг 8. Миграции базы данных и финальная запись',
                'Preview the migration list, test credentials, write the generated .env file, and optionally apply all pending migrations.' => 'Просмотрите список миграций, проверьте учетные данные, запишите созданный файл .env и при необходимости примените все ожидающие миграции.',
                'Generated .env preview' => 'Предпросмотр созданного .env',
                'Test database' => 'Проверить базу данных',
                'Run migrations' => 'Запустить миграции',
                'Preview .env' => 'Предпросмотр .env',
                'Write .env and lock installer' => 'Записать .env и заблокировать установщик',
                'Run migrations after writing .env' => 'Запустить миграции после записи .env',
                'The installer form expired. Reload the page and try again.' => 'Форма установщика устарела. Обновите страницу и попробуйте снова.',
                'Database migrations failed:' => 'Миграции базы данных завершились ошибкой:',
                'The .env file was written, but migrations failed:' => 'Файл .env был записан, но миграции завершились ошибкой:',
                'Review the generated .env preview below before writing it to the server.' => 'Проверьте созданный ниже предпросмотр .env перед записью файла на сервер.',
                'success' => 'успех',
                'error' => 'ошибка',
                'ok' => 'готово',
                'warn' => 'внимание',
                'fail' => 'ошибка',
                'applied' => 'применено',
                'pending' => 'ожидает',
                'Step 2. Runtime profile and public URLs' => 'Шаг 2. Профиль запуска и публичные URL',
                'Choose the deployment mode and the exact HTTPS URLs that users and Telegram will open.' => 'Выберите режим развертывания и точные HTTPS-адреса, которые будут открывать пользователи и Telegram.',
                'Runtime profile' => 'Профиль запуска',
                'Use production for the public website and telegram for a Mini App-first deployment.' => 'Используйте production для публичного сайта и telegram для развертывания с приоритетом Mini App.',
                'Public website URL' => 'URL публичного сайта',
                'Canonical website URL used for public pages and shared links.' => 'Канонический URL сайта для публичных страниц и ссылок общего доступа.',
                'Telegram Mini App URL' => 'URL Telegram Mini App',
                'HTTPS URL configured in BotFather for the Mini App.' => 'HTTPS URL, настроенный в BotFather для Mini App.',
                'Staging URL' => 'URL тестового стенда',
                'Local URL' => 'Локальный URL',
                'Show PHP errors' => 'Показывать ошибки PHP',
                'Keep disabled for production unless a controlled troubleshooting window is active.' => 'Оставьте выключенным в production, кроме контролируемого периода диагностики.',
                'Use minified application bundle' => 'Использовать минифицированный пакет приложения',
                'Enable preconnect hints' => 'Включить подсказки preconnect',
                'Load vendor assets from CDN' => 'Загружать vendor-ресурсы из CDN',
                'Step 3. Telegram bot and Mini App entry points' => 'Шаг 3. Telegram-бот и точки входа Mini App',
                'Configure the bot identity, server-side bot token, webhook secret, and worker tokens used by Telegram flows.' => 'Настройте идентификатор бота, серверный токен бота, секрет вебхука и токены воркеров для Telegram-сценариев.',
                'Bot username' => 'Имя пользователя бота',
                'Username only, without the @ prefix.' => 'Только имя пользователя, без префикса @.',
                'Bot token' => 'Токен бота',
                'Webhook secret' => 'Секрет вебхука',
                'Alert worker token' => 'Токен воркера алертов',
                'Leave blank to generate a safe token.' => 'Оставьте пустым, чтобы создать безопасный токен.',
                'Search refresh token' => 'Токен обновления поиска',
                'Step 4. MySQL or MariaDB database' => 'Шаг 4. База данных MySQL или MariaDB',
                'Enter the hosting database credentials. The installer can test the connection and run all pending SQL migrations.' => 'Введите учетные данные базы данных хостинга. Установщик может проверить подключение и запустить все ожидающие SQL-миграции.',
                'Database host' => 'Хост базы данных',
                'Helper field used to build MYSQL_DSN.' => 'Вспомогательное поле для создания MYSQL_DSN.',
                'Database port' => 'Порт базы данных',
                'Optional helper field.' => 'Необязательное вспомогательное поле.',
                'Database name' => 'Имя базы данных',
                'Database charset' => 'Кодировка базы данных',
                'Use utf8mb4 for production.' => 'Используйте utf8mb4 для production.',
                'Database user' => 'Пользователь базы данных',
                'Database password' => 'Пароль базы данных',
                'Step 5. Providers, cache, and AI' => 'Шаг 5. Провайдеры, кеш и AI',
                'Configure provider keys and cache services. Secrets stay in .env and are not rendered to the public app.' => 'Настройте ключи провайдеров и сервисы кеша. Секреты остаются в .env и не выводятся в публичное приложение.',
                'CoinGecko API plan' => 'Тариф CoinGecko API',
                'CoinGecko API key' => 'Ключ CoinGecko API',
                'Upstash Redis REST URL' => 'URL Upstash Redis REST',
                'Upstash Redis REST token' => 'Токен Upstash Redis REST',
                'AI provider' => 'AI-провайдер',
                'AI prompt version' => 'Версия AI-промпта',
                'AI enabled features' => 'Включенные AI-функции',
                'Comma-separated subset of summary, sentiment, insight.' => 'Список через запятую из summary, sentiment, insight.',
                'AI fallback behavior' => 'Поведение AI при недоступности',
                'Sentiment cache TTL seconds' => 'TTL кеша sentiment в секундах',
                'Groq API key' => 'Ключ Groq API',
                'Groq model id' => 'ID модели Groq',
                'Groq base URL' => 'Базовый URL Groq',
                'Groq timeout seconds' => 'Таймаут Groq в секундах',
                'Groq rate-limit window seconds' => 'Окно rate-limit Groq в секундах',
                'Groq max requests per window' => 'Максимум запросов Groq за окно',
                'ChangeNOW link id' => 'ID ссылки ChangeNOW',
                'Step 6. Feature flags and product limits' => 'Шаг 6. Флаги функций и лимиты продукта',
                'Start with optional features disabled, then enable each feature after readiness checks pass.' => 'Начните с отключенными необязательными функциями, затем включайте каждую после успешных проверок готовности.',
                'AI insights' => 'AI-инсайты',
                'Smart alerts' => 'Умные алерты',
                'Exchange widget alias' => 'Псевдоним виджета обмена',
                'ChangeNOW exchange widget' => 'Виджет обмена ChangeNOW',
                'TON Connect' => 'TON Connect',
                'Referral attribution' => 'Реферальная атрибуция',
                'Achievements' => 'Достижения',
                'Telegram Stars premium' => 'Премиум Telegram Stars',
                'Alert max rules per user' => 'Максимум правил алертов на пользователя',
                'Alert default frequency cap seconds' => 'Стандартная задержка алертов в секундах',
                'Alert max deliveries per day' => 'Максимум доставок алертов в день',
                'Alert evaluation interval seconds' => 'Интервал проверки алертов в секундах',
                'Achievement weekly check days' => 'Дней для недельной проверки достижений',
                'Achievement share milestone count' => 'Порог шарингов для достижения',
                'Achievement movement threshold percent' => 'Порог движения для достижения, %',
                'Achievement max prompts per session' => 'Максимум prompts достижений за сессию',
                'Premium plan code' => 'Код премиум-плана',
                'Premium monthly Stars' => 'Stars в месяц для премиума',
                'Premium period seconds' => 'Период премиума в секундах',
                'Premium signing secret' => 'Секрет подписи премиума',
                'Step 7. Operations, admin, and performance budgets' => 'Шаг 7. Операции, администрирование и бюджеты производительности',
                'Optional store paths, admin tokens, logging controls, readiness behavior, and performance budgets.' => 'Необязательные пути хранилищ, токены администратора, управление логами, поведение readiness и бюджеты производительности.',
                'Installer enabled after .env exists' => 'Установщик включен после появления .env',
                'The saved .env sets this to false to lock the installer.' => 'Сохраненный .env устанавливает false, чтобы заблокировать установщик.',
                'Installer re-entry token' => 'Токен повторного входа в установщик',
                'Required in the URL as ?token=... when re-enabling the installer.' => 'Требуется в URL как ?token=... при повторном включении установщика.',
                'API audit log' => 'Аудит-лог API',
                'Active readiness probes' => 'Активные readiness-проверки',
                'Observability log level' => 'Уровень логов observability',
                'Verbose tracing' => 'Подробная трассировка',
                'Client error reporting' => 'Отправка клиентских ошибок',
                'TON curation file' => 'Файл курирования TON',
                'TON curation token' => 'Токен курирования TON',
                'Admin store path' => 'Путь хранилища админки',
                'Admin token' => 'Токен администратора',
                'Admin support token' => 'Токен поддержки администратора',
                'Cache enabled override' => 'Переопределение включения кеша',
                'Rate limit enabled override' => 'Переопределение включения rate limit',
                'Budget first contentful render ms' => 'Бюджет first contentful render, мс',
                'Budget app ready ms' => 'Бюджет готовности приложения, мс',
                'Budget market API p95 ms' => 'Бюджет market API p95, мс',
                'Budget search API p95 ms' => 'Бюджет search API p95, мс',
                'Budget coin detail API p95 ms' => 'Бюджет coin detail API p95, мс',
                'Budget chart render ms' => 'Бюджет рендера графика, мс',
                'Budget alert delivery p95 ms' => 'Бюджет доставки алерта p95, мс',
                'Budget share card generation p95 ms' => 'Бюджет создания share card p95, мс',
                'Immutable asset max-age seconds' => 'max-age неизменяемых ресурсов, сек',
                'Unversioned asset max-age seconds' => 'max-age ресурсов без версии, сек',
                'Asset stale-while-revalidate seconds' => 'stale-while-revalidate ресурсов, сек',
                'Manifest max-age seconds' => 'max-age манифеста, сек',
                'PHP version' => 'Версия PHP',
                'Running PHP %s; PHP 8.1+ is required.' => 'Запущен PHP %s; требуется PHP 8.1+.',
                'PHP extension %s' => 'Расширение PHP %s',
                'Loaded.' => 'Загружено.',
                'Enable this extension in the hosting PHP selector.' => 'Включите это расширение в переключателе PHP на хостинге.',
                '.env write access' => 'Доступ на запись .env',
                'The installer needs write access to create or replace .env. If this is not available, use the generated .env preview.' => 'Установщику нужен доступ на запись, чтобы создать или заменить .env. Если доступа нет, используйте созданный предпросмотр .env.',
                'HTTPS request' => 'HTTPS-запрос',
                'Production and Telegram Mini App deployments must use HTTPS.' => 'Production и Telegram Mini App должны работать через HTTPS.',
                'Installer is available because no .env file exists yet.' => 'Установщик доступен, потому что файл .env еще не существует.',
                'Installer is locked because .env exists and TONBANKCARD_INSTALLER_ENABLED is not true.' => 'Установщик заблокирован, потому что .env существует, а TONBANKCARD_INSTALLER_ENABLED не равно true.',
                'Set TONBANKCARD_INSTALLER_TOKEN before re-opening the installer.' => 'Укажите TONBANKCARD_INSTALLER_TOKEN перед повторным открытием установщика.',
                'Installer token accepted.' => 'Токен установщика принят.',
                'Installer is locked. Add the configured token to the URL as ?token=...' => 'Установщик заблокирован. Добавьте настроенный токен в URL как ?token=...',
                'Could not create a backup of the existing .env file.' => 'Не удалось создать резервную копию существующего файла .env.',
                'Could not write .env. Check write permissions on the repository root.' => 'Не удалось записать .env. Проверьте права на запись в корне репозитория.',
                'The .env file was written and the installer was locked.' => 'Файл .env записан, установщик заблокирован.',
                'Database connection succeeded.' => 'Подключение к базе данных успешно.',
                'Database connection failed:' => 'Подключение к базе данных завершилось ошибкой:',
                'MYSQL_DSN and MYSQL_USER are required before testing the database.' => 'MYSQL_DSN и MYSQL_USER обязательны перед проверкой базы данных.',
                'No pending migrations.' => 'Нет ожидающих миграций.',
                'Applied %d migration(s).' => 'Применено миграций: %d.',
                'TONBANKCARD_PROFILE must be local, staging, production, or telegram.' => 'TONBANKCARD_PROFILE должен быть local, staging, production или telegram.',
                '%s must be a valid absolute HTTP(S) %s.' => '%s должен быть корректным абсолютным HTTP(S) %s.',
                '%s is required for non-local installs (%s).' => '%s обязателен для нелокальной установки (%s).',
                'UPSTASH_REDIS_REST_URL must be a valid absolute HTTP(S) URL.' => 'UPSTASH_REDIS_REST_URL должен быть корректным абсолютным HTTP(S) URL.',
                'MYSQL_DSN must start with mysql:.' => 'MYSQL_DSN должен начинаться с mysql:.',
                'COINGECKO_API_KEY is required when COINGECKO_API_PLAN is pro.' => 'COINGECKO_API_KEY обязателен, когда COINGECKO_API_PLAN равен pro.',
                'TONBANKCARD_BOT_TOKEN is required for the telegram profile or alerts.' => 'TONBANKCARD_BOT_TOKEN обязателен для профиля telegram или алертов.',
                'GROQ_API_KEY is required when TONBANKCARD_FEATURE_AI is true.' => 'GROQ_API_KEY обязателен, когда TONBANKCARD_FEATURE_AI равно true.',
                'CHANGENOW_LINK_ID is required when TONBANKCARD_FEATURE_CHANGENOW is true.' => 'CHANGENOW_LINK_ID обязателен, когда TONBANKCARD_FEATURE_CHANGENOW равно true.',
                'local base URL' => 'локальный URL',
                'staging URL' => 'URL тестового стенда',
                'public website URL' => 'URL публичного сайта',
                'Telegram Mini App URL' => 'URL Telegram Mini App',
                'Telegram bot username' => 'имя пользователя Telegram-бота',
                'Upstash Redis REST URL' => 'URL Upstash Redis REST',
                'Upstash Redis REST token' => 'токен Upstash Redis REST',
                'MySQL DSN' => 'MySQL DSN',
                'MySQL user' => 'пользователь MySQL',
                'MySQL password' => 'пароль MySQL',
            ],
        ];
    }
}

if ( ! function_exists( 'tonbankcard_installer_translate' ) ) {
    /**
     * @param string $text
     * @param string $language
     * @return string
     */
    function tonbankcard_installer_translate( string $text, string $language = 'en' ) {
        $language = tonbankcard_installer_normalize_language( $language );
        if ( 'en' === $language ) {
            return $text;
        }

        $translations = tonbankcard_installer_translations();
        return isset( $translations[ $language ][ $text ] ) ? $translations[ $language ][ $text ] : $text;
    }
}

if ( ! function_exists( 'tonbankcard_installer_localize_copy' ) ) {
    /**
     * @param array $value
     * @param string $language
     * @return array
     */
    function tonbankcard_installer_localize_copy( array $value, string $language ) {
        foreach ( [ 'title', 'description', 'label', 'help' ] as $key ) {
            if ( isset( $value[ $key ] ) && is_string( $value[ $key ] ) ) {
                $value[ $key ] = tonbankcard_installer_translate( $value[ $key ], $language );
            }
        }

        return $value;
    }
}

if ( ! function_exists( 'tonbankcard_installer_field_groups' ) ) {
    /**
     * Returns grouped form fields for the installation wizard.
     *
     * @param string $language
     * @return array
     */
    function tonbankcard_installer_field_groups( $language = 'en' ) {
        $groups = [
            'runtime'      => [
                'title'       => 'Step 2. Runtime profile and public URLs',
                'description' => 'Choose the deployment mode and the exact HTTPS URLs that users and Telegram will open.',
                'fields'      => [
                    'TONBANKCARD_PROFILE'           => [
                        'label'   => 'Runtime profile',
                        'type'    => 'select',
                        'options' => [ 'production', 'telegram', 'staging', 'local' ],
                        'help'    => 'Use production for the public website and telegram for a Mini App-first deployment.',
                    ],
                    'TONBANKCARD_PUBLIC_BASE_URL'   => [
                        'label'       => 'Public website URL',
                        'type'        => 'url',
                        'placeholder' => 'https://marketcap.example.com/',
                        'help'        => 'Canonical website URL used for public pages and shared links.',
                    ],
                    'TONBANKCARD_TELEGRAM_BASE_URL' => [
                        'label'       => 'Telegram Mini App URL',
                        'type'        => 'url',
                        'placeholder' => 'https://miniapp.example.com/',
                        'help'        => 'HTTPS URL configured in BotFather for the Mini App.',
                    ],
                    'TONBANKCARD_STAGING_BASE_URL'  => [
                        'label'       => 'Staging URL',
                        'type'        => 'url',
                        'placeholder' => 'https://staging-marketcap.example.com/',
                    ],
                    'TONBANKCARD_LOCAL_BASE_URL'    => [
                        'label'       => 'Local URL',
                        'type'        => 'url',
                        'placeholder' => 'http://localhost:8888/',
                    ],
                    'TONBANKCARD_DEBUG'             => [
                        'label' => 'Show PHP errors',
                        'type'  => 'boolean',
                        'help'  => 'Keep disabled for production unless a controlled troubleshooting window is active.',
                    ],
                    'TONBANKCARD_APP_MINIFIED'      => [
                        'label' => 'Use minified application bundle',
                        'type'  => 'boolean',
                    ],
                    'TONBANKCARD_PRECONNECT'        => [
                        'label' => 'Enable preconnect hints',
                        'type'  => 'boolean',
                    ],
                    'TONBANKCARD_CDN'               => [
                        'label' => 'Load vendor assets from CDN',
                        'type'  => 'boolean',
                    ],
                ],
            ],
            'telegram'     => [
                'title'       => 'Step 3. Telegram bot and Mini App entry points',
                'description' => 'Configure the bot identity, server-side bot token, webhook secret, and worker tokens used by Telegram flows.',
                'fields'      => [
                    'TONBANKCARD_BOT_USERNAME'       => [
                        'label'       => 'Bot username',
                        'type'        => 'text',
                        'placeholder' => 'tonbankcard_bot',
                        'help'        => 'Username only, without the @ prefix.',
                    ],
                    'TONBANKCARD_BOT_TOKEN'          => [
                        'label'       => 'Bot token',
                        'type'        => 'password',
                        'placeholder' => '123456:ABC...',
                        'secret'      => TRUE,
                    ],
                    'TONBANKCARD_BOT_WEBHOOK_SECRET' => [
                        'label'  => 'Webhook secret',
                        'type'   => 'password',
                        'secret' => TRUE,
                    ],
                    'TONBANKCARD_ALERT_WORKER_TOKEN' => [
                        'label'  => 'Alert worker token',
                        'type'   => 'password',
                        'secret' => TRUE,
                        'help'   => 'Leave blank to generate a safe token.',
                    ],
                    'TONBANKCARD_SEARCH_REFRESH_TOKEN' => [
                        'label'  => 'Search refresh token',
                        'type'   => 'password',
                        'secret' => TRUE,
                        'help'   => 'Leave blank to generate a safe token.',
                    ],
                ],
            ],
            'database'     => [
                'title'       => 'Step 4. MySQL or MariaDB database',
                'description' => 'Enter the hosting database credentials. The installer can test the connection and run all pending SQL migrations.',
                'fields'      => [
                    'MYSQL_HOST'     => [
                        'label'       => 'Database host',
                        'type'        => 'text',
                        'placeholder' => '127.0.0.1',
                        'help'        => 'Helper field used to build MYSQL_DSN.',
                    ],
                    'MYSQL_PORT'     => [
                        'label'       => 'Database port',
                        'type'        => 'number',
                        'placeholder' => '3306',
                        'help'        => 'Optional helper field.',
                    ],
                    'MYSQL_DATABASE' => [
                        'label'       => 'Database name',
                        'type'        => 'text',
                        'placeholder' => 'marketcap',
                        'help'        => 'Helper field used to build MYSQL_DSN.',
                    ],
                    'MYSQL_CHARSET'  => [
                        'label'       => 'Database charset',
                        'type'        => 'text',
                        'placeholder' => 'utf8mb4',
                        'help'        => 'Use utf8mb4 for production.',
                    ],
                    'MYSQL_DSN'      => [
                        'label'       => 'MYSQL_DSN',
                        'type'        => 'text',
                        'placeholder' => 'mysql:host=127.0.0.1;dbname=marketcap;charset=utf8mb4',
                    ],
                    'MYSQL_USER'     => [
                        'label'       => 'Database user',
                        'type'        => 'text',
                        'placeholder' => 'marketcap',
                    ],
                    'MYSQL_PASSWORD' => [
                        'label'  => 'Database password',
                        'type'   => 'password',
                        'secret' => TRUE,
                    ],
                ],
            ],
            'providers'    => [
                'title'       => 'Step 5. Providers, cache, and AI',
                'description' => 'Configure provider keys and cache services. Secrets stay in .env and are not rendered to the public app.',
                'fields'      => [
                    'COINGECKO_API_PLAN'         => [
                        'label'   => 'CoinGecko API plan',
                        'type'    => 'select',
                        'options' => [ 'demo', 'pro' ],
                    ],
                    'COINGECKO_API_KEY'          => [
                        'label'  => 'CoinGecko API key',
                        'type'   => 'password',
                        'secret' => TRUE,
                    ],
                    'UPSTASH_REDIS_REST_URL'     => [
                        'label'       => 'Upstash Redis REST URL',
                        'type'        => 'url',
                        'placeholder' => 'https://example.upstash.io',
                    ],
                    'UPSTASH_REDIS_REST_TOKEN'   => [
                        'label'  => 'Upstash Redis REST token',
                        'type'   => 'password',
                        'secret' => TRUE,
                    ],
                    'TONBANKCARD_AI_PROVIDER'    => [
                        'label'   => 'AI provider',
                        'type'    => 'select',
                        'options' => [ 'groq' ],
                    ],
                    'TONBANKCARD_AI_PROMPT_VERSION' => [
                        'label' => 'AI prompt version',
                        'type'  => 'text',
                    ],
                    'TONBANKCARD_AI_ENABLED_FEATURES' => [
                        'label' => 'AI enabled features',
                        'type'  => 'text',
                        'help'  => 'Comma-separated subset of summary, sentiment, insight.',
                    ],
                    'TONBANKCARD_AI_FALLBACK_BEHAVIOR' => [
                        'label'   => 'AI fallback behavior',
                        'type'    => 'select',
                        'options' => [ 'unavailable' ],
                    ],
                    'TONBANKCARD_SENTIMENT_CACHE_TTL_SECONDS' => [
                        'label' => 'Sentiment cache TTL seconds',
                        'type'  => 'number',
                    ],
                    'GROQ_API_KEY'               => [
                        'label'  => 'Groq API key',
                        'type'   => 'password',
                        'secret' => TRUE,
                    ],
                    'GROQ_MODEL_ID'              => [
                        'label' => 'Groq model id',
                        'type'  => 'text',
                    ],
                    'GROQ_BASE_URL'              => [
                        'label' => 'Groq base URL',
                        'type'  => 'url',
                    ],
                    'GROQ_TIMEOUT_SECONDS'       => [
                        'label' => 'Groq timeout seconds',
                        'type'  => 'number',
                    ],
                    'GROQ_RATE_LIMIT_WINDOW_SECONDS' => [
                        'label' => 'Groq rate-limit window seconds',
                        'type'  => 'number',
                    ],
                    'GROQ_RATE_LIMIT_MAX_REQUESTS' => [
                        'label' => 'Groq max requests per window',
                        'type'  => 'number',
                    ],
                    'CHANGENOW_LINK_ID'          => [
                        'label' => 'ChangeNOW link id',
                        'type'  => 'text',
                    ],
                ],
            ],
            'features'     => [
                'title'       => 'Step 6. Feature flags and product limits',
                'description' => 'Start with optional features disabled, then enable each feature after readiness checks pass.',
                'fields'      => [
                    'TONBANKCARD_FEATURE_AI'           => [ 'label' => 'AI insights', 'type' => 'boolean' ],
                    'TONBANKCARD_FEATURE_ALERTS'       => [ 'label' => 'Smart alerts', 'type' => 'boolean' ],
                    'TONBANKCARD_FEATURE_WIDGET'       => [ 'label' => 'Exchange widget alias', 'type' => 'boolean' ],
                    'TONBANKCARD_FEATURE_CHANGENOW'    => [ 'label' => 'ChangeNOW exchange widget', 'type' => 'boolean' ],
                    'TONBANKCARD_FEATURE_TON_CONNECT'  => [ 'label' => 'TON Connect', 'type' => 'boolean' ],
                    'TONBANKCARD_FEATURE_REFERRALS'    => [ 'label' => 'Referral attribution', 'type' => 'boolean' ],
                    'TONBANKCARD_FEATURE_GAMIFICATION' => [ 'label' => 'Achievements', 'type' => 'boolean' ],
                    'TONBANKCARD_FEATURE_PREMIUM'      => [ 'label' => 'Telegram Stars premium', 'type' => 'boolean' ],
                    'TONBANKCARD_ALERT_MAX_RULES_PER_USER' => [ 'label' => 'Alert max rules per user', 'type' => 'number' ],
                    'TONBANKCARD_ALERT_DEFAULT_FREQUENCY_CAP_SECONDS' => [ 'label' => 'Alert default frequency cap seconds', 'type' => 'number' ],
                    'TONBANKCARD_ALERT_MAX_DELIVERIES_PER_DAY' => [ 'label' => 'Alert max deliveries per day', 'type' => 'number' ],
                    'TONBANKCARD_ALERT_EVALUATION_INTERVAL_SECONDS' => [ 'label' => 'Alert evaluation interval seconds', 'type' => 'number' ],
                    'TONBANKCARD_ACHIEVEMENT_WEEKLY_CHECK_DAYS' => [ 'label' => 'Achievement weekly check days', 'type' => 'number' ],
                    'TONBANKCARD_ACHIEVEMENT_SHARE_MILESTONE_COUNT' => [ 'label' => 'Achievement share milestone count', 'type' => 'number' ],
                    'TONBANKCARD_ACHIEVEMENT_MOVEMENT_THRESHOLD_PERCENT' => [ 'label' => 'Achievement movement threshold percent', 'type' => 'number' ],
                    'TONBANKCARD_ACHIEVEMENT_MAX_PROMPTS_PER_SESSION' => [ 'label' => 'Achievement max prompts per session', 'type' => 'number' ],
                    'TONBANKCARD_PREMIUM_PLAN_CODE' => [ 'label' => 'Premium plan code', 'type' => 'text' ],
                    'TONBANKCARD_PREMIUM_MONTHLY_STARS' => [ 'label' => 'Premium monthly Stars', 'type' => 'number' ],
                    'TONBANKCARD_PREMIUM_SUBSCRIPTION_PERIOD_SECONDS' => [ 'label' => 'Premium period seconds', 'type' => 'number' ],
                    'TONBANKCARD_PREMIUM_SIGNING_SECRET' => [ 'label' => 'Premium signing secret', 'type' => 'password', 'secret' => TRUE ],
                ],
            ],
            'operations'   => [
                'title'       => 'Step 7. Operations, admin, and performance budgets',
                'description' => 'Optional store paths, admin tokens, logging controls, readiness behavior, and performance budgets.',
                'fields'      => [
                    'TONBANKCARD_INSTALLER_ENABLED' => [
                        'label' => 'Installer enabled after .env exists',
                        'type'  => 'boolean',
                        'help'  => 'The saved .env sets this to false to lock the installer.',
                    ],
                    'TONBANKCARD_INSTALLER_TOKEN'   => [
                        'label'  => 'Installer re-entry token',
                        'type'   => 'password',
                        'secret' => TRUE,
                        'help'   => 'Required in the URL as ?token=... when re-enabling the installer.',
                    ],
                    'TONBANKCARD_API_AUDIT_LOG'     => [ 'label' => 'API audit log', 'type' => 'boolean' ],
                    'TONBANKCARD_API_ACTIVE_READINESS' => [ 'label' => 'Active readiness probes', 'type' => 'boolean' ],
                    'TONBANKCARD_OBSERVABILITY_LOG_LEVEL' => [
                        'label'   => 'Observability log level',
                        'type'    => 'select',
                        'options' => [ 'warning', 'debug', 'info', 'error', 'critical', 'off' ],
                    ],
                    'TONBANKCARD_VERBOSE_TRACING' => [ 'label' => 'Verbose tracing', 'type' => 'boolean' ],
                    'TONBANKCARD_CLIENT_ERROR_REPORTING' => [ 'label' => 'Client error reporting', 'type' => 'boolean' ],
                    'TONBANKCARD_TON_CURATION_FILE' => [ 'label' => 'TON curation file', 'type' => 'text' ],
                    'TONBANKCARD_TON_CURATION_TOKEN' => [ 'label' => 'TON curation token', 'type' => 'password', 'secret' => TRUE ],
                    'TONBANKCARD_ADMIN_STORE' => [ 'label' => 'Admin store path', 'type' => 'text' ],
                    'TONBANKCARD_ADMIN_TOKEN' => [ 'label' => 'Admin token', 'type' => 'password', 'secret' => TRUE ],
                    'TONBANKCARD_ADMIN_SUPPORT_TOKEN' => [ 'label' => 'Admin support token', 'type' => 'password', 'secret' => TRUE ],
                    'TONBANKCARD_CACHE_ENABLED' => [ 'label' => 'Cache enabled override', 'type' => 'boolean' ],
                    'TONBANKCARD_RATE_LIMIT_ENABLED' => [ 'label' => 'Rate limit enabled override', 'type' => 'boolean' ],
                    'TONBANKCARD_BUDGET_FIRST_CONTENTFUL_RENDER_MS' => [ 'label' => 'Budget first contentful render ms', 'type' => 'number' ],
                    'TONBANKCARD_BUDGET_APP_READY_MS' => [ 'label' => 'Budget app ready ms', 'type' => 'number' ],
                    'TONBANKCARD_BUDGET_MARKET_API_P95_MS' => [ 'label' => 'Budget market API p95 ms', 'type' => 'number' ],
                    'TONBANKCARD_BUDGET_SEARCH_API_P95_MS' => [ 'label' => 'Budget search API p95 ms', 'type' => 'number' ],
                    'TONBANKCARD_BUDGET_COIN_DETAIL_API_P95_MS' => [ 'label' => 'Budget coin detail API p95 ms', 'type' => 'number' ],
                    'TONBANKCARD_BUDGET_CHART_RENDER_MS' => [ 'label' => 'Budget chart render ms', 'type' => 'number' ],
                    'TONBANKCARD_BUDGET_ALERT_DELIVERY_P95_MS' => [ 'label' => 'Budget alert delivery p95 ms', 'type' => 'number' ],
                    'TONBANKCARD_BUDGET_SHARE_CARD_GENERATION_P95_MS' => [ 'label' => 'Budget share card generation p95 ms', 'type' => 'number' ],
                    'TONBANKCARD_STATIC_ASSET_IMMUTABLE_MAX_AGE_SECONDS' => [ 'label' => 'Immutable asset max-age seconds', 'type' => 'number' ],
                    'TONBANKCARD_STATIC_ASSET_UNVERSIONED_MAX_AGE_SECONDS' => [ 'label' => 'Unversioned asset max-age seconds', 'type' => 'number' ],
                    'TONBANKCARD_STATIC_ASSET_STALE_REVALIDATE_SECONDS' => [ 'label' => 'Asset stale-while-revalidate seconds', 'type' => 'number' ],
                    'TONBANKCARD_STATIC_ASSET_MANIFEST_MAX_AGE_SECONDS' => [ 'label' => 'Manifest max-age seconds', 'type' => 'number' ],
                ],
            ],
        ];

        $language = tonbankcard_installer_normalize_language( $language );
        if ( 'en' === $language ) {
            return $groups;
        }

        foreach ( $groups as $group_key => $group ) {
            $group = tonbankcard_installer_localize_copy( $group, $language );
            foreach ( $group['fields'] as $field_key => $definition ) {
                $group['fields'][ $field_key ] = tonbankcard_installer_localize_copy( $definition, $language );
            }
            $groups[ $group_key ] = $group;
        }

        return $groups;
    }
}

if ( ! function_exists( 'tonbankcard_installer_field_definitions' ) ) {
    /**
     * @return array
     */
    function tonbankcard_installer_field_definitions() {
        $fields = [];
        foreach ( tonbankcard_installer_field_groups() as $group_key => $group ) {
            foreach ( $group['fields'] as $key => $definition ) {
                $definition['group'] = $group_key;
                if ( empty( $definition['label'] ) ) {
                    $definition['label'] = $key;
                }
                if ( empty( $definition['type'] ) ) {
                    $definition['type'] = 'text';
                }
                $fields[ $key ] = $definition;
            }
        }

        return $fields;
    }
}

if ( ! function_exists( 'tonbankcard_installer_field_keys' ) ) {
    /**
     * @param string|null $root
     * @return array
     */
    function tonbankcard_installer_field_keys( $root = null ) {
        $keys = array_keys( tonbankcard_installer_field_definitions() );
        $example = tonbankcard_installer_parse_env_file( tonbankcard_installer_env_example_path( $root ) );
        foreach ( array_keys( $example ) as $key ) {
            if ( ! in_array( $key, $keys, TRUE ) ) {
                $keys[] = $key;
            }
        }

        return $keys;
    }
}

if ( ! function_exists( 'tonbankcard_installer_env_keys' ) ) {
    /**
     * @param string|null $root
     * @return array
     */
    function tonbankcard_installer_env_keys( $root = null ) {
        $example = tonbankcard_installer_parse_env_file( tonbankcard_installer_env_example_path( $root ) );
        $keys = array_keys( $example );

        foreach ( [ 'TONBANKCARD_INSTALLER_ENABLED', 'TONBANKCARD_INSTALLER_TOKEN' ] as $key ) {
            if ( ! in_array( $key, $keys, TRUE ) ) {
                $keys[] = $key;
            }
        }

        return $keys;
    }
}

if ( ! function_exists( 'tonbankcard_installer_default_values' ) ) {
    /**
     * @param string|null $root
     * @return array
     */
    function tonbankcard_installer_default_values( $root = null ) {
        $root = null === $root ? tonbankcard_installer_root_dir() : rtrim( (string) $root, '/' );
        $values = tonbankcard_installer_parse_env_file( tonbankcard_installer_env_example_path( $root ) );
        $existing = tonbankcard_installer_parse_env_file( tonbankcard_installer_env_path( $root ) );
        $values = array_merge( $values, $existing );

        foreach ( tonbankcard_installer_field_keys( $root ) as $key ) {
            if ( ! array_key_exists( $key, $values ) ) {
                $values[ $key ] = '';
            }
        }

        if ( empty( $existing ) && isset( $values['TONBANKCARD_PROFILE'] ) && 'local' === $values['TONBANKCARD_PROFILE'] ) {
            $values['TONBANKCARD_PROFILE'] = 'production';
        }

        if ( empty( $values['MYSQL_HOST'] ) ) {
            $values['MYSQL_HOST'] = tonbankcard_installer_mysql_dsn_part( isset( $values['MYSQL_DSN'] ) ? $values['MYSQL_DSN'] : '', 'host' );
        }
        if ( empty( $values['MYSQL_PORT'] ) ) {
            $values['MYSQL_PORT'] = tonbankcard_installer_mysql_dsn_part( isset( $values['MYSQL_DSN'] ) ? $values['MYSQL_DSN'] : '', 'port' );
        }
        if ( empty( $values['MYSQL_DATABASE'] ) ) {
            $values['MYSQL_DATABASE'] = tonbankcard_installer_mysql_dsn_part( isset( $values['MYSQL_DSN'] ) ? $values['MYSQL_DSN'] : '', 'dbname' );
        }
        if ( empty( $values['MYSQL_CHARSET'] ) ) {
            $values['MYSQL_CHARSET'] = tonbankcard_installer_mysql_dsn_part( isset( $values['MYSQL_DSN'] ) ? $values['MYSQL_DSN'] : '', 'charset' );
        }
        if ( empty( $values['MYSQL_CHARSET'] ) ) {
            $values['MYSQL_CHARSET'] = 'utf8mb4';
        }

        return $values;
    }
}

if ( ! function_exists( 'tonbankcard_installer_mysql_dsn_part' ) ) {
    /**
     * @param string $dsn
     * @param string $part
     * @return string
     */
    function tonbankcard_installer_mysql_dsn_part( string $dsn, string $part ) {
        if ( 0 !== strpos( $dsn, 'mysql:' ) ) {
            return '';
        }

        $segments = explode( ';', substr( $dsn, 6 ) );
        foreach ( $segments as $segment ) {
            $separator = strpos( $segment, '=' );
            if ( FALSE === $separator ) {
                continue;
            }

            $key = trim( substr( $segment, 0, $separator ) );
            $value = trim( substr( $segment, $separator + 1 ) );
            if ( $key === $part ) {
                return $value;
            }
        }

        return '';
    }
}

if ( ! function_exists( 'tonbankcard_installer_build_mysql_dsn' ) ) {
    /**
     * @param string $host
     * @param string $port
     * @param string $database
     * @param string $charset
     * @return string
     */
    function tonbankcard_installer_build_mysql_dsn( string $host, string $port, string $database, string $charset = 'utf8mb4' ) {
        $host = trim( $host );
        $port = trim( $port );
        $database = trim( $database );
        $charset = trim( $charset );

        if ( '' === $host || '' === $database ) {
            return '';
        }

        $dsn = 'mysql:host=' . $host;
        if ( '' !== $port ) {
            $dsn .= ';port=' . $port;
        }
        $dsn .= ';dbname=' . $database;
        if ( '' !== $charset ) {
            $dsn .= ';charset=' . $charset;
        }

        return $dsn;
    }
}

if ( ! function_exists( 'tonbankcard_installer_generate_token' ) ) {
    /**
     * @return string
     */
    function tonbankcard_installer_generate_token() {
        try {
            return bin2hex( random_bytes( 24 ) );
        } catch ( Exception $exception ) {
            return hash( 'sha256', uniqid( 'tonbankcard-installer-', TRUE ) . mt_rand() );
        }
    }
}

if ( ! function_exists( 'tonbankcard_installer_prepare_values' ) ) {
    /**
     * Normalizes submitted values and derives helper values before saving.
     *
     * @param array $input
     * @param string|null $root
     * @return array
     */
    function tonbankcard_installer_prepare_values( array $input, $root = null ) {
        $values = tonbankcard_installer_default_values( $root );
        foreach ( tonbankcard_installer_field_keys( $root ) as $key ) {
            if ( array_key_exists( $key, $input ) ) {
                $values[ $key ] = is_array( $input[ $key ] ) ? '' : trim( (string) $input[ $key ] );
            }
        }

        if ( empty( $values['MYSQL_DSN'] ) ) {
            $values['MYSQL_DSN'] = tonbankcard_installer_build_mysql_dsn(
                isset( $values['MYSQL_HOST'] ) ? $values['MYSQL_HOST'] : '',
                isset( $values['MYSQL_PORT'] ) ? $values['MYSQL_PORT'] : '',
                isset( $values['MYSQL_DATABASE'] ) ? $values['MYSQL_DATABASE'] : '',
                isset( $values['MYSQL_CHARSET'] ) ? $values['MYSQL_CHARSET'] : 'utf8mb4'
            );
        }

        foreach ( [ 'TONBANKCARD_ALERT_WORKER_TOKEN', 'TONBANKCARD_SEARCH_REFRESH_TOKEN' ] as $key ) {
            if ( array_key_exists( $key, $values ) && '' === trim( (string) $values[ $key ] ) ) {
                $values[ $key ] = tonbankcard_installer_generate_token();
            }
        }

        $values['TONBANKCARD_INSTALLER_ENABLED'] = 'false';

        foreach ( tonbankcard_installer_boolean_keys() as $key ) {
            if ( array_key_exists( $key, $values ) ) {
                $values[ $key ] = tonbankcard_installer_normalize_bool( $values[ $key ], $values[ $key ] );
            }
        }

        return $values;
    }
}

if ( ! function_exists( 'tonbankcard_installer_boolean_keys' ) ) {
    /**
     * @return array
     */
    function tonbankcard_installer_boolean_keys() {
        $keys = [];
        foreach ( tonbankcard_installer_field_definitions() as $key => $definition ) {
            if ( isset( $definition['type'] ) && 'boolean' === $definition['type'] ) {
                $keys[] = $key;
            }
        }

        return $keys;
    }
}

if ( ! function_exists( 'tonbankcard_installer_normalize_bool' ) ) {
    /**
     * @param mixed $value
     * @param mixed $default
     * @return string
     */
    function tonbankcard_installer_normalize_bool( $value, $default = 'false' ) {
        if ( is_bool( $value ) ) {
            return $value ? 'true' : 'false';
        }

        $normalized = strtolower( trim( (string) $value ) );
        if ( in_array( $normalized, [ '1', 'true', 'yes', 'on' ], TRUE ) ) {
            return 'true';
        }
        if ( in_array( $normalized, [ '0', 'false', 'no', 'off', '' ], TRUE ) ) {
            return 'false';
        }

        return tonbankcard_installer_normalize_bool( $default, 'false' );
    }
}

if ( ! function_exists( 'tonbankcard_installer_format_env_value' ) ) {
    /**
     * @param mixed $value
     * @return string
     */
    function tonbankcard_installer_format_env_value( $value ) {
        $value = (string) $value;
        if ( '' === $value ) {
            return '';
        }

        if ( preg_match( '/^[A-Za-z0-9._:\/@%+,;=?-]+$/', $value ) && FALSE === strpos( $value, '#' ) ) {
            return $value;
        }

        return '"' . str_replace( [ '\\', "\r", "\n", '"' ], [ '\\\\', '\\r', '\\n', '\\"' ], $value ) . '"';
    }
}

if ( ! function_exists( 'tonbankcard_installer_render_env' ) ) {
    /**
     * @param array $values
     * @param string|null $root
     * @return string
     */
    function tonbankcard_installer_render_env( array $values, $root = null ) {
        $prepared = tonbankcard_installer_prepare_values( $values, $root );
        $lines = [
            '# Generated by the TONBANKCARD automatic hosting installer.',
            '# Keep this file private. The Apache .htaccess blocks dotfile downloads.',
        ];

        foreach ( tonbankcard_installer_env_keys( $root ) as $key ) {
            $value = array_key_exists( $key, $prepared ) ? $prepared[ $key ] : '';
            $lines[] = $key . '=' . tonbankcard_installer_format_env_value( $value );
        }

        return implode( "\n", $lines ) . "\n";
    }
}

if ( ! function_exists( 'tonbankcard_installer_write_env' ) ) {
    /**
     * @param string $root
     * @param array $values
     * @param string $language
     * @return array
     */
    function tonbankcard_installer_write_env( string $root, array $values, string $language = 'en' ) {
        $path = tonbankcard_installer_env_path( $root );
        $env = tonbankcard_installer_render_env( $values, $root );
        $backup = null;

        if ( is_file( $path ) ) {
            $backup = $path . '.' . gmdate( 'YmdHis' ) . '.bak';
            if ( ! copy( $path, $backup ) ) {
                return [
                    'ok'      => FALSE,
                    'message' => tonbankcard_installer_translate( 'Could not create a backup of the existing .env file.', $language ),
                    'env'     => $env,
                ];
            }
        }

        if ( FALSE === file_put_contents( $path, $env, LOCK_EX ) ) {
            return [
                'ok'      => FALSE,
                'message' => tonbankcard_installer_translate( 'Could not write .env. Check write permissions on the repository root.', $language ),
                'env'     => $env,
            ];
        }

        @chmod( $path, 0600 );

        return [
            'ok'      => TRUE,
            'message' => tonbankcard_installer_translate( 'The .env file was written and the installer was locked.', $language ),
            'path'    => $path,
            'backup'  => $backup,
            'env'     => $env,
        ];
    }
}

if ( ! function_exists( 'tonbankcard_installer_is_secret_key' ) ) {
    /**
     * @param string $key
     * @return bool
     */
    function tonbankcard_installer_is_secret_key( string $key ) {
        if ( in_array( $key, [ 'TONBANKCARD_BOT_USERNAME' ], TRUE ) ) {
            return FALSE;
        }

        return (bool) preg_match( '/(PASSWORD|TOKEN|SECRET|API_KEY|SIGNING_SECRET)$/', $key );
    }
}

if ( ! function_exists( 'tonbankcard_installer_display_value' ) ) {
    /**
     * @param string $key
     * @param mixed $value
     * @return string
     */
    function tonbankcard_installer_display_value( string $key, $value ) {
        $value = (string) $value;
        if ( '' === trim( $value ) ) {
            return '';
        }

        if ( tonbankcard_installer_is_secret_key( $key ) ) {
            return 'configured';
        }

        return $value;
    }
}

if ( ! function_exists( 'tonbankcard_installer_value_enabled' ) ) {
    /**
     * @param array $values
     * @param string $key
     * @return bool
     */
    function tonbankcard_installer_value_enabled( array $values, string $key ) {
        return isset( $values[ $key ] ) && 'true' === tonbankcard_installer_normalize_bool( $values[ $key ] );
    }
}

if ( ! function_exists( 'tonbankcard_installer_valid_url' ) ) {
    /**
     * @param string $value
     * @return bool
     */
    function tonbankcard_installer_valid_url( string $value ) {
        $parts = parse_url( trim( $value ) );
        return is_array( $parts )
            && ! empty( $parts['scheme'] )
            && in_array( strtolower( $parts['scheme'] ), [ 'http', 'https' ], TRUE )
            && ! empty( $parts['host'] );
    }
}

if ( ! function_exists( 'tonbankcard_installer_validate_values' ) ) {
    /**
     * Validates high-risk runtime values before writing .env.
     *
     * @param array $values
     * @param string $language
     * @return array
     */
    function tonbankcard_installer_validate_values( array $values, string $language = 'en' ) {
        $prepared = tonbankcard_installer_prepare_values( $values );
        $errors = [];
        $profile = isset( $prepared['TONBANKCARD_PROFILE'] ) ? $prepared['TONBANKCARD_PROFILE'] : 'production';

        if ( ! in_array( $profile, [ 'local', 'staging', 'production', 'telegram' ], TRUE ) ) {
            $errors[] = tonbankcard_installer_translate( 'TONBANKCARD_PROFILE must be local, staging, production, or telegram.', $language );
        }

        $required_urls = [];
        if ( 'local' === $profile ) {
            $required_urls['TONBANKCARD_LOCAL_BASE_URL'] = tonbankcard_installer_translate( 'local base URL', $language );
        } elseif ( 'staging' === $profile ) {
            $required_urls['TONBANKCARD_STAGING_BASE_URL'] = tonbankcard_installer_translate( 'staging URL', $language );
        } elseif ( 'production' === $profile ) {
            $required_urls['TONBANKCARD_PUBLIC_BASE_URL'] = tonbankcard_installer_translate( 'public website URL', $language );
        } elseif ( 'telegram' === $profile ) {
            $required_urls['TONBANKCARD_PUBLIC_BASE_URL'] = tonbankcard_installer_translate( 'public website URL', $language );
            $required_urls['TONBANKCARD_TELEGRAM_BASE_URL'] = tonbankcard_installer_translate( 'Telegram Mini App URL', $language );
        }

        foreach ( $required_urls as $key => $label ) {
            if ( empty( $prepared[ $key ] ) || ! tonbankcard_installer_valid_url( $prepared[ $key ] ) ) {
                $errors[] = sprintf( tonbankcard_installer_translate( '%s must be a valid absolute HTTP(S) %s.', $language ), $key, $label );
            }
        }

        if ( 'local' !== $profile ) {
            foreach ( [
                'TONBANKCARD_BOT_USERNAME'     => tonbankcard_installer_translate( 'Telegram bot username', $language ),
                'UPSTASH_REDIS_REST_URL'       => tonbankcard_installer_translate( 'Upstash Redis REST URL', $language ),
                'UPSTASH_REDIS_REST_TOKEN'     => tonbankcard_installer_translate( 'Upstash Redis REST token', $language ),
                'MYSQL_DSN'                    => tonbankcard_installer_translate( 'MySQL DSN', $language ),
                'MYSQL_USER'                   => tonbankcard_installer_translate( 'MySQL user', $language ),
                'MYSQL_PASSWORD'               => tonbankcard_installer_translate( 'MySQL password', $language ),
            ] as $key => $label ) {
                if ( empty( $prepared[ $key ] ) ) {
                    $errors[] = sprintf( tonbankcard_installer_translate( '%s is required for non-local installs (%s).', $language ), $key, $label );
                }
            }
        }

        if ( ! empty( $prepared['UPSTASH_REDIS_REST_URL'] ) && ! tonbankcard_installer_valid_url( $prepared['UPSTASH_REDIS_REST_URL'] ) ) {
            $errors[] = tonbankcard_installer_translate( 'UPSTASH_REDIS_REST_URL must be a valid absolute HTTP(S) URL.', $language );
        }

        if ( ! empty( $prepared['MYSQL_DSN'] ) && 0 !== strpos( $prepared['MYSQL_DSN'], 'mysql:' ) ) {
            $errors[] = tonbankcard_installer_translate( 'MYSQL_DSN must start with mysql:.', $language );
        }

        if ( isset( $prepared['COINGECKO_API_PLAN'] ) && 'pro' === strtolower( (string) $prepared['COINGECKO_API_PLAN'] ) && empty( $prepared['COINGECKO_API_KEY'] ) ) {
            $errors[] = tonbankcard_installer_translate( 'COINGECKO_API_KEY is required when COINGECKO_API_PLAN is pro.', $language );
        }

        if ( 'telegram' === $profile || tonbankcard_installer_value_enabled( $prepared, 'TONBANKCARD_FEATURE_ALERTS' ) ) {
            if ( empty( $prepared['TONBANKCARD_BOT_TOKEN'] ) ) {
                $errors[] = tonbankcard_installer_translate( 'TONBANKCARD_BOT_TOKEN is required for the telegram profile or alerts.', $language );
            }
        }

        if ( tonbankcard_installer_value_enabled( $prepared, 'TONBANKCARD_FEATURE_AI' ) && empty( $prepared['GROQ_API_KEY'] ) ) {
            $errors[] = tonbankcard_installer_translate( 'GROQ_API_KEY is required when TONBANKCARD_FEATURE_AI is true.', $language );
        }

        if ( tonbankcard_installer_value_enabled( $prepared, 'TONBANKCARD_FEATURE_CHANGENOW' ) && empty( $prepared['CHANGENOW_LINK_ID'] ) ) {
            $errors[] = tonbankcard_installer_translate( 'CHANGENOW_LINK_ID is required when TONBANKCARD_FEATURE_CHANGENOW is true.', $language );
        }

        return $errors;
    }
}

if ( ! function_exists( 'tonbankcard_installer_lock_state' ) ) {
    /**
     * Determines whether the installer can run.
     *
     * @param string $root
     * @param array $query
     * @param string $language
     * @return array
     */
    function tonbankcard_installer_lock_state( string $root, array $query, string $language = 'en' ) {
        $path = tonbankcard_installer_env_path( $root );
        if ( ! is_file( $path ) ) {
            return [
                'allowed'   => TRUE,
                'first_run' => TRUE,
                'locked'    => FALSE,
                'message'   => tonbankcard_installer_translate( 'Installer is available because no .env file exists yet.', $language ),
            ];
        }

        $env = tonbankcard_installer_parse_env_file( $path );
        $enabled = isset( $env['TONBANKCARD_INSTALLER_ENABLED'] )
            ? tonbankcard_installer_normalize_bool( $env['TONBANKCARD_INSTALLER_ENABLED'] )
            : 'false';

        if ( 'true' !== $enabled ) {
            return [
                'allowed'   => FALSE,
                'first_run' => FALSE,
                'locked'    => TRUE,
                'message'   => tonbankcard_installer_translate( 'Installer is locked because .env exists and TONBANKCARD_INSTALLER_ENABLED is not true.', $language ),
            ];
        }

        $token = isset( $env['TONBANKCARD_INSTALLER_TOKEN'] ) ? trim( (string) $env['TONBANKCARD_INSTALLER_TOKEN'] ) : '';
        if ( '' === $token ) {
            return [
                'allowed'        => FALSE,
                'first_run'      => FALSE,
                'locked'         => TRUE,
                'token_required' => TRUE,
                'message'        => tonbankcard_installer_translate( 'Set TONBANKCARD_INSTALLER_TOKEN before re-opening the installer.', $language ),
            ];
        }

        $provided = isset( $query['token'] ) ? trim( (string) $query['token'] ) : '';
        if ( hash_equals( $token, $provided ) ) {
            return [
                'allowed'     => TRUE,
                'first_run'   => FALSE,
                'locked'      => FALSE,
                'token_valid' => TRUE,
                'message'     => tonbankcard_installer_translate( 'Installer token accepted.', $language ),
            ];
        }

        return [
            'allowed'        => FALSE,
            'first_run'      => FALSE,
            'locked'         => TRUE,
            'token_required' => TRUE,
            'message'        => tonbankcard_installer_translate( 'Installer is locked. Add the configured token to the URL as ?token=...', $language ),
        ];
    }
}

if ( ! function_exists( 'tonbankcard_installer_system_checks' ) ) {
    /**
     * @param string $root
     * @param string $language
     * @return array
     */
    function tonbankcard_installer_system_checks( string $root, string $language = 'en' ) {
        $extensions = [ 'pdo', 'pdo_mysql', 'json', 'hash', 'openssl', 'filter', 'session' ];
        $checks = [
            [
                'name'    => tonbankcard_installer_translate( 'PHP version', $language ),
                'status'  => version_compare( PHP_VERSION, '8.1.0', '>=' ) ? 'ok' : 'fail',
                'message' => sprintf( tonbankcard_installer_translate( 'Running PHP %s; PHP 8.1+ is required.', $language ), PHP_VERSION ),
            ],
        ];

        foreach ( $extensions as $extension ) {
            $checks[] = [
                'name'    => sprintf( tonbankcard_installer_translate( 'PHP extension %s', $language ), $extension ),
                'status'  => extension_loaded( $extension ) ? 'ok' : 'fail',
                'message' => extension_loaded( $extension )
                    ? tonbankcard_installer_translate( 'Loaded.', $language )
                    : tonbankcard_installer_translate( 'Enable this extension in the hosting PHP selector.', $language ),
            ];
        }

        $env_path = tonbankcard_installer_env_path( $root );
        $checks[] = [
            'name'    => tonbankcard_installer_translate( '.env write access', $language ),
            'status'  => ( is_file( $env_path ) && is_writable( $env_path ) ) || ( ! is_file( $env_path ) && is_writable( $root ) ) ? 'ok' : 'warn',
            'message' => tonbankcard_installer_translate( 'The installer needs write access to create or replace .env. If this is not available, use the generated .env preview.', $language ),
        ];

        $checks[] = [
            'name'    => tonbankcard_installer_translate( 'HTTPS request', $language ),
            'status'  => ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== strtolower( (string) $_SERVER['HTTPS'] ) ) ? 'ok' : 'warn',
            'message' => tonbankcard_installer_translate( 'Production and Telegram Mini App deployments must use HTTPS.', $language ),
        ];

        return $checks;
    }
}

if ( ! function_exists( 'tonbankcard_installer_database_connection' ) ) {
    /**
     * @param array $values
     * @param string $language
     * @return PDO
     */
    function tonbankcard_installer_database_connection( array $values, string $language = 'en' ) {
        if ( ! class_exists( 'PDO' ) ) {
            throw new RuntimeException( 'PDO is not available.' );
        }

        $prepared = tonbankcard_installer_prepare_values( $values );
        $dsn = isset( $prepared['MYSQL_DSN'] ) ? trim( (string) $prepared['MYSQL_DSN'] ) : '';
        $user = isset( $prepared['MYSQL_USER'] ) ? (string) $prepared['MYSQL_USER'] : '';
        $password = isset( $prepared['MYSQL_PASSWORD'] ) ? (string) $prepared['MYSQL_PASSWORD'] : '';

        if ( '' === $dsn || '' === $user ) {
            throw new RuntimeException( tonbankcard_installer_translate( 'MYSQL_DSN and MYSQL_USER are required before testing the database.', $language ) );
        }

        return new PDO(
            $dsn,
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
}

if ( ! function_exists( 'tonbankcard_installer_test_database' ) ) {
    /**
     * @param array $values
     * @param string $language
     * @return array
     */
    function tonbankcard_installer_test_database( array $values, string $language = 'en' ) {
        try {
            $pdo = tonbankcard_installer_database_connection( $values, $language );
            $pdo->query( 'SELECT 1' );

            return [
                'ok'      => TRUE,
                'message' => tonbankcard_installer_translate( 'Database connection succeeded.', $language ),
            ];
        } catch ( Throwable $error ) {
            return [
                'ok'      => FALSE,
                'message' => tonbankcard_installer_translate( 'Database connection failed:', $language ) . ' ' . $error->getMessage(),
            ];
        }
    }
}

if ( ! function_exists( 'tonbankcard_installer_migration_files' ) ) {
    /**
     * @param string $dir
     * @return array
     */
    function tonbankcard_installer_migration_files( string $dir ) {
        if ( ! is_dir( $dir ) ) {
            throw new RuntimeException( 'Migration directory does not exist: ' . $dir );
        }

        $files = glob( rtrim( $dir, '/' ) . '/*.up.sql' );
        if ( ! is_array( $files ) ) {
            $files = [];
        }
        sort( $files, SORT_NATURAL );

        $migrations = [];
        foreach ( $files as $file ) {
            $version = basename( $file, '.up.sql' );
            $migrations[ $version ] = $file;
        }

        return $migrations;
    }
}

if ( ! function_exists( 'tonbankcard_installer_migration_plan' ) ) {
    /**
     * @param string $dir
     * @param array $applied_versions
     * @return array
     */
    function tonbankcard_installer_migration_plan( string $dir, array $applied_versions = [] ) {
        $applied = array_fill_keys( $applied_versions, TRUE );
        $plan = [];

        foreach ( tonbankcard_installer_migration_files( $dir ) as $version => $file ) {
            $plan[] = [
                'version' => $version,
                'file'    => $file,
                'status'  => isset( $applied[ $version ] ) ? 'applied' : 'pending',
            ];
        }

        return $plan;
    }
}

if ( ! function_exists( 'tonbankcard_installer_ensure_migration_ledger' ) ) {
    /**
     * @param PDO $pdo
     * @return void
     */
    function tonbankcard_installer_ensure_migration_ledger( PDO $pdo ) {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS `schema_migrations` (
                `version` VARCHAR(191) NOT NULL,
                `description` VARCHAR(255) NOT NULL,
                `checksum` CHAR(64) NOT NULL,
                `applied_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                PRIMARY KEY (`version`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }
}

if ( ! function_exists( 'tonbankcard_installer_applied_migrations' ) ) {
    /**
     * @param PDO $pdo
     * @return array
     */
    function tonbankcard_installer_applied_migrations( PDO $pdo ) {
        tonbankcard_installer_ensure_migration_ledger( $pdo );
        $rows = $pdo->query( 'SELECT version FROM schema_migrations ORDER BY version' )->fetchAll();
        $versions = [];
        foreach ( $rows as $row ) {
            if ( isset( $row['version'] ) ) {
                $versions[] = $row['version'];
            }
        }

        return $versions;
    }
}

if ( ! function_exists( 'tonbankcard_installer_sql_statements' ) ) {
    /**
     * @param string $sql
     * @return array
     */
    function tonbankcard_installer_sql_statements( string $sql ) {
        $statements = [];
        $buffer = '';
        $quote = null;
        $line_comment = FALSE;
        $block_comment = FALSE;
        $length = strlen( $sql );

        for ( $i = 0; $i < $length; $i++ ) {
            $char = $sql[ $i ];
            $next = ( $i + 1 < $length ) ? $sql[ $i + 1 ] : '';
            $buffer .= $char;

            if ( $line_comment ) {
                if ( "\n" === $char ) {
                    $line_comment = FALSE;
                }
                continue;
            }

            if ( $block_comment ) {
                if ( '*' === $char && '/' === $next ) {
                    $buffer .= $next;
                    $i++;
                    $block_comment = FALSE;
                }
                continue;
            }

            if ( null !== $quote ) {
                if ( ( "'" === $quote || '"' === $quote ) && '\\' === $char && '' !== $next ) {
                    $buffer .= $next;
                    $i++;
                    continue;
                }

                if ( $quote === $char ) {
                    if ( '' !== $next && $quote === $next ) {
                        $buffer .= $next;
                        $i++;
                        continue;
                    }

                    $quote = null;
                }
                continue;
            }

            if ( '-' === $char && '-' === $next && tonbankcard_installer_is_dash_comment_start( $sql, $i ) ) {
                $buffer .= $next;
                $i++;
                $line_comment = TRUE;
                continue;
            }

            if ( '#' === $char ) {
                $line_comment = TRUE;
                continue;
            }

            if ( '/' === $char && '*' === $next ) {
                $buffer .= $next;
                $i++;
                $block_comment = TRUE;
                continue;
            }

            if ( "'" === $char || '"' === $char || '`' === $char ) {
                $quote = $char;
                continue;
            }

            if ( ';' === $char ) {
                $statement = trim( substr( $buffer, 0, -1 ) );
                if ( '' !== $statement ) {
                    $statements[] = $statement;
                }
                $buffer = '';
            }
        }

        $statement = trim( $buffer );
        if ( '' !== $statement ) {
            $statements[] = $statement;
        }

        return $statements;
    }
}

if ( ! function_exists( 'tonbankcard_installer_is_dash_comment_start' ) ) {
    /**
     * @param string $sql
     * @param int $offset
     * @return bool
     */
    function tonbankcard_installer_is_dash_comment_start( string $sql, int $offset ) {
        $next_offset = $offset + 2;
        if ( ! isset( $sql[ $next_offset ] ) ) {
            return TRUE;
        }

        return ctype_space( $sql[ $next_offset ] );
    }
}

if ( ! function_exists( 'tonbankcard_installer_strip_leading_comments' ) ) {
    /**
     * @param string $sql
     * @return string
     */
    function tonbankcard_installer_strip_leading_comments( string $sql ) {
        $offset = 0;
        $length = strlen( $sql );

        while ( $offset < $length ) {
            while ( $offset < $length && ctype_space( $sql[ $offset ] ) ) {
                $offset++;
            }

            if ( $offset >= $length ) {
                return '';
            }

            if ( '#' === $sql[ $offset ] ) {
                $line_end = strpos( $sql, "\n", $offset );
                if ( FALSE === $line_end ) {
                    return '';
                }
                $offset = $line_end + 1;
                continue;
            }

            if ( '-' === $sql[ $offset ] && isset( $sql[ $offset + 1 ] ) && '-' === $sql[ $offset + 1 ] && tonbankcard_installer_is_dash_comment_start( $sql, $offset ) ) {
                $line_end = strpos( $sql, "\n", $offset );
                if ( FALSE === $line_end ) {
                    return '';
                }
                $offset = $line_end + 1;
                continue;
            }

            if ( '/' === $sql[ $offset ] && isset( $sql[ $offset + 1 ] ) && '*' === $sql[ $offset + 1 ] ) {
                $comment_end = strpos( $sql, '*/', $offset + 2 );
                if ( FALSE === $comment_end ) {
                    return '';
                }
                $offset = $comment_end + 2;
                continue;
            }

            break;
        }

        return ltrim( substr( $sql, $offset ) );
    }
}

if ( ! function_exists( 'tonbankcard_installer_unquote_identifier' ) ) {
    /**
     * @param string $identifier
     * @return string
     */
    function tonbankcard_installer_unquote_identifier( string $identifier ) {
        $identifier = trim( $identifier );
        if ( strlen( $identifier ) >= 2 && '`' === $identifier[0] && '`' === $identifier[ strlen( $identifier ) - 1 ] ) {
            return str_replace( '``', '`', substr( $identifier, 1, -1 ) );
        }

        return trim( $identifier, "` \t\r\n" );
    }
}

if ( ! function_exists( 'tonbankcard_installer_split_sql_list' ) ) {
    /**
     * @param string $sql
     * @return array
     */
    function tonbankcard_installer_split_sql_list( string $sql ) {
        $items = [];
        $buffer = '';
        $quote = null;
        $line_comment = FALSE;
        $block_comment = FALSE;
        $depth = 0;
        $length = strlen( $sql );

        for ( $i = 0; $i < $length; $i++ ) {
            $char = $sql[ $i ];
            $next = ( $i + 1 < $length ) ? $sql[ $i + 1 ] : '';
            $buffer .= $char;

            if ( $line_comment ) {
                if ( "\n" === $char ) {
                    $line_comment = FALSE;
                }
                continue;
            }

            if ( $block_comment ) {
                if ( '*' === $char && '/' === $next ) {
                    $buffer .= $next;
                    $i++;
                    $block_comment = FALSE;
                }
                continue;
            }

            if ( null !== $quote ) {
                if ( ( "'" === $quote || '"' === $quote ) && '\\' === $char && '' !== $next ) {
                    $buffer .= $next;
                    $i++;
                    continue;
                }

                if ( $quote === $char ) {
                    if ( '' !== $next && $quote === $next ) {
                        $buffer .= $next;
                        $i++;
                        continue;
                    }

                    $quote = null;
                }
                continue;
            }

            if ( '-' === $char && '-' === $next && tonbankcard_installer_is_dash_comment_start( $sql, $i ) ) {
                $buffer .= $next;
                $i++;
                $line_comment = TRUE;
                continue;
            }

            if ( '#' === $char ) {
                $line_comment = TRUE;
                continue;
            }

            if ( '/' === $char && '*' === $next ) {
                $buffer .= $next;
                $i++;
                $block_comment = TRUE;
                continue;
            }

            if ( "'" === $char || '"' === $char || '`' === $char ) {
                $quote = $char;
                continue;
            }

            if ( '(' === $char ) {
                $depth++;
                continue;
            }

            if ( ')' === $char && $depth > 0 ) {
                $depth--;
                continue;
            }

            if ( ',' === $char && 0 === $depth ) {
                $item = trim( substr( $buffer, 0, -1 ) );
                if ( '' !== $item ) {
                    $items[] = $item;
                }
                $buffer = '';
            }
        }

        $item = trim( $buffer );
        if ( '' !== $item ) {
            $items[] = $item;
        }

        return $items;
    }
}

if ( ! function_exists( 'tonbankcard_installer_parse_alter_table' ) ) {
    /**
     * @param string $statement
     * @return array|null
     */
    function tonbankcard_installer_parse_alter_table( string $statement ) {
        $normalized = tonbankcard_installer_strip_leading_comments( $statement );
        if ( ! preg_match( '/\AALTER\s+TABLE\s+((?:`(?:``|[^`])+`)|[A-Za-z0-9_]+)\s+(.+)\z/is', $normalized, $matches ) ) {
            return null;
        }

        return [
            'table_sql' => $matches[1],
            'table'     => tonbankcard_installer_unquote_identifier( $matches[1] ),
            'clauses'   => tonbankcard_installer_split_sql_list( $matches[2] ),
        ];
    }
}

if ( ! function_exists( 'tonbankcard_installer_column_exists' ) ) {
    /**
     * @param PDO $pdo
     * @param string $table
     * @param string $column
     * @return bool
     */
    function tonbankcard_installer_column_exists( PDO $pdo, string $table, string $column ) {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) AS found FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
        );
        $statement->execute(
            [
                ':table'  => $table,
                ':column' => $column,
            ]
        );
        $row = $statement->fetch();

        return is_array( $row ) && isset( $row['found'] ) && (int) $row['found'] > 0;
    }
}

if ( ! function_exists( 'tonbankcard_installer_index_exists' ) ) {
    /**
     * @param PDO $pdo
     * @param string $table
     * @param string $index
     * @return bool
     */
    function tonbankcard_installer_index_exists( PDO $pdo, string $table, string $index ) {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) AS found FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index'
        );
        $statement->execute(
            [
                ':table' => $table,
                ':index' => $index,
            ]
        );
        $row = $statement->fetch();

        return is_array( $row ) && isset( $row['found'] ) && (int) $row['found'] > 0;
    }
}

if ( ! function_exists( 'tonbankcard_installer_should_skip_alter_clause' ) ) {
    /**
     * @param PDO $pdo
     * @param string $table
     * @param string $clause
     * @return bool
     */
    function tonbankcard_installer_should_skip_alter_clause( PDO $pdo, string $table, string $clause ) {
        $normalized = tonbankcard_installer_strip_leading_comments( $clause );

        if ( preg_match( '/\AADD\s+(?:UNIQUE\s+|FULLTEXT\s+|SPATIAL\s+)?(?:KEY|INDEX)\s+((?:`(?:``|[^`])+`)|[A-Za-z0-9_]+)/i', $normalized, $matches ) ) {
            return tonbankcard_installer_index_exists( $pdo, $table, tonbankcard_installer_unquote_identifier( $matches[1] ) );
        }

        if ( preg_match( '/\AADD\s+COLUMN\s+((?:`(?:``|[^`])+`)|[A-Za-z0-9_]+)/i', $normalized, $matches ) ) {
            return tonbankcard_installer_column_exists( $pdo, $table, tonbankcard_installer_unquote_identifier( $matches[1] ) );
        }

        if ( preg_match( '/\AADD\s+((?:`(?:``|[^`])+`)|[A-Za-z0-9_]+)/i', $normalized, $matches ) ) {
            $identifier = tonbankcard_installer_unquote_identifier( $matches[1] );
            if ( '`' !== $matches[1][0] && in_array( strtoupper( $identifier ), [ 'UNIQUE', 'FULLTEXT', 'SPATIAL', 'KEY', 'INDEX', 'PRIMARY', 'CONSTRAINT', 'FOREIGN', 'CHECK' ], TRUE ) ) {
                return FALSE;
            }

            return tonbankcard_installer_column_exists( $pdo, $table, $identifier );
        }

        if ( preg_match( '/\ADROP\s+(?:KEY|INDEX)\s+((?:`(?:``|[^`])+`)|[A-Za-z0-9_]+)/i', $normalized, $matches ) ) {
            return ! tonbankcard_installer_index_exists( $pdo, $table, tonbankcard_installer_unquote_identifier( $matches[1] ) );
        }

        if ( preg_match( '/\ADROP\s+COLUMN\s+((?:`(?:``|[^`])+`)|[A-Za-z0-9_]+)/i', $normalized, $matches ) ) {
            return ! tonbankcard_installer_column_exists( $pdo, $table, tonbankcard_installer_unquote_identifier( $matches[1] ) );
        }

        return FALSE;
    }
}

if ( ! function_exists( 'tonbankcard_installer_exec_guarded_alter' ) ) {
    /**
     * @param PDO $pdo
     * @param string $statement
     * @return bool
     */
    function tonbankcard_installer_exec_guarded_alter( PDO $pdo, string $statement ) {
        $alter = tonbankcard_installer_parse_alter_table( $statement );
        if ( null === $alter ) {
            return FALSE;
        }

        $clauses = [];
        foreach ( $alter['clauses'] as $clause ) {
            if ( tonbankcard_installer_should_skip_alter_clause( $pdo, $alter['table'], $clause ) ) {
                continue;
            }

            $clauses[] = $clause;
        }

        if ( empty( $clauses ) ) {
            return TRUE;
        }

        $pdo->exec( 'ALTER TABLE ' . $alter['table_sql'] . "\n  " . implode( ",\n  ", $clauses ) );

        return TRUE;
    }
}

if ( ! function_exists( 'tonbankcard_installer_exec_statement' ) ) {
    /**
     * @param PDO $pdo
     * @param string $statement
     * @return void
     */
    function tonbankcard_installer_exec_statement( PDO $pdo, string $statement ) {
        if ( tonbankcard_installer_exec_guarded_alter( $pdo, $statement ) ) {
            return;
        }

        $pdo->exec( $statement );
    }
}

if ( ! function_exists( 'tonbankcard_installer_acquire_migration_lock' ) ) {
    /**
     * @param PDO $pdo
     * @return void
     */
    function tonbankcard_installer_acquire_migration_lock( PDO $pdo ) {
        $statement = $pdo->prepare( 'SELECT GET_LOCK(:lock_name, :timeout_seconds) AS acquired' );
        $statement->execute(
            [
                ':lock_name'       => 'tonbankcard_migrations',
                ':timeout_seconds' => 30,
            ]
        );
        $row = $statement->fetch();

        if ( ! is_array( $row ) || ! isset( $row['acquired'] ) || 1 !== (int) $row['acquired'] ) {
            throw new RuntimeException( 'Could not acquire migration lock: tonbankcard_migrations' );
        }
    }
}

if ( ! function_exists( 'tonbankcard_installer_release_migration_lock' ) ) {
    /**
     * @param PDO $pdo
     * @return void
     */
    function tonbankcard_installer_release_migration_lock( PDO $pdo ) {
        $statement = $pdo->prepare( 'SELECT RELEASE_LOCK(:lock_name) AS released' );
        $statement->execute( [ ':lock_name' => 'tonbankcard_migrations' ] );
    }
}

if ( ! function_exists( 'tonbankcard_installer_with_migration_lock' ) ) {
    /**
     * @param PDO $pdo
     * @param callable $callback
     * @return mixed
     */
    function tonbankcard_installer_with_migration_lock( PDO $pdo, callable $callback ) {
        tonbankcard_installer_acquire_migration_lock( $pdo );

        try {
            return $callback();
        } finally {
            tonbankcard_installer_release_migration_lock( $pdo );
        }
    }
}

if ( ! function_exists( 'tonbankcard_installer_is_comment_only_statement' ) ) {
    /**
     * @param string $part
     * @return bool
     */
    function tonbankcard_installer_is_comment_only_statement( string $part ) {
        return '' === tonbankcard_installer_strip_leading_comments( $part );
    }
}

if ( ! function_exists( 'tonbankcard_installer_filter_comment_only_statements' ) ) {
    /**
     * @param array $parts
     * @return array
     */
    function tonbankcard_installer_filter_comment_only_statements( array $parts ) {
        $statements = [];
        foreach ( $parts as $part ) {
            $statement = trim( $part );
            if ( '' === $statement ) {
                continue;
            }

            if ( tonbankcard_installer_is_comment_only_statement( $statement ) ) {
                continue;
            }

            $statements[] = $statement;
        }

        return $statements;
    }
}

if ( ! function_exists( 'tonbankcard_installer_exec_migration_file' ) ) {
    /**
     * @param PDO $pdo
     * @param string $file
     * @return void
     */
    function tonbankcard_installer_exec_migration_file( PDO $pdo, string $file ) {
        $sql = file_get_contents( $file );
        if ( FALSE === $sql ) {
            throw new RuntimeException( 'Cannot read migration file: ' . $file );
        }

        foreach ( tonbankcard_installer_filter_comment_only_statements( tonbankcard_installer_sql_statements( $sql ) ) as $statement ) {
            tonbankcard_installer_exec_statement( $pdo, $statement );
        }
    }
}

if ( ! function_exists( 'tonbankcard_installer_migration_description' ) ) {
    /**
     * @param string $version
     * @return string
     */
    function tonbankcard_installer_migration_description( string $version ) {
        $description = preg_replace( '/^[0-9]+_?/', '', $version );
        $description = str_replace( '_', ' ', (string) $description );

        return trim( $description ) ?: $version;
    }
}

if ( ! function_exists( 'tonbankcard_installer_apply_migrations' ) ) {
    /**
     * @param array $values
     * @param string $dir
     * @param string $language
     * @return array
     */
    function tonbankcard_installer_apply_migrations( array $values, string $dir, string $language = 'en' ) {
        $pdo = tonbankcard_installer_database_connection( $values, $language );
        return tonbankcard_installer_with_migration_lock(
            $pdo,
            function () use ( $pdo, $dir, $language ) {
                tonbankcard_installer_ensure_migration_ledger( $pdo );
                $applied = array_fill_keys( tonbankcard_installer_applied_migrations( $pdo ), TRUE );
                $applied_now = [];

                foreach ( tonbankcard_installer_migration_files( $dir ) as $version => $file ) {
                    if ( isset( $applied[ $version ] ) ) {
                        continue;
                    }

                    tonbankcard_installer_exec_migration_file( $pdo, $file );

                    $insert = $pdo->prepare(
                        'INSERT INTO schema_migrations (version, description, checksum) VALUES (:version, :description, :checksum)'
                    );
                    $insert->execute(
                        [
                            ':version'     => $version,
                            ':description' => tonbankcard_installer_migration_description( $version ),
                            ':checksum'    => hash_file( 'sha256', $file ),
                        ]
                    );

                    $applied_now[] = $version;
                }

                return [
                    'ok'       => TRUE,
                    'applied'  => $applied_now,
                    'message'  => empty( $applied_now )
                        ? tonbankcard_installer_translate( 'No pending migrations.', $language )
                        : sprintf( tonbankcard_installer_translate( 'Applied %d migration(s).', $language ), count( $applied_now ) ),
                ];
            }
        );
    }
}
