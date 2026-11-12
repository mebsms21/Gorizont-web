<?php
defined( 'ABSPATH' ) || exit;
function gorizont_demo_p( $text, $class = '' ) {
    $attrs = $class ? ' ' . wp_json_encode( array( 'className' => $class ), JSON_UNESCAPED_UNICODE ) : '';
    return '<!-- wp:paragraph' . $attrs . ' --><p' . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . '>' . wp_kses_post( $text ) . '</p><!-- /wp:paragraph -->';
}
function gorizont_demo_h( $text, $level = 2, $size = 'x-large' ) {
    return '<!-- wp:heading ' . wp_json_encode( array( 'level' => $level, 'fontSize' => $size ) ) . ' --><h' . $level . ' class="wp-block-heading has-' . esc_attr( $size ) . '-font-size">' . wp_kses_post( $text ) . '</h' . $level . '><!-- /wp:heading -->';
}
function gorizont_demo_group( $content, $class = '', $wide = true ) {
    $attrs = array( 'className' => $class, 'layout' => array( 'type' => 'constrained' ) );
    if ( $wide ) { $attrs['align'] = 'wide'; }
    return '<!-- wp:group ' . wp_json_encode( $attrs ) . ' --><div class="wp-block-group' . ( $wide ? ' alignwide' : '' ) . ( $class ? ' ' . esc_attr( $class ) : '' ) . '">' . $content . '</div><!-- /wp:group -->';
}
function gorizont_demo_buttons( $items ) {
    $html = '<!-- wp:buttons --><div class="wp-block-buttons">';
    foreach ( $items as $item ) {
        $outline = ! empty( $item[2] );
        $html .= '<!-- wp:button' . ( $outline ? ' {"className":"is-style-outline"}' : '' ) . ' --><div class="wp-block-button' . ( $outline ? ' is-style-outline' : '' ) . '"><a class="wp-block-button__link wp-element-button" href="' . esc_url( home_url( $item[1] ) ) . '">' . esc_html( $item[0] ) . '</a></div><!-- /wp:button -->';
    }
    return $html . '</div><!-- /wp:buttons -->';
}
function gorizont_demo_image( $id, $class = '' ) {
    $attrs = array( 'id' => $id, 'sizeSlug' => 'full', 'linkDestination' => 'none', 'align' => 'wide', 'className' => $class );
    return '<!-- wp:image ' . wp_json_encode( $attrs ) . ' --><figure class="wp-block-image alignwide size-full' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '"><img src="' . esc_url( wp_get_attachment_url( $id ) ) . '" alt="' . esc_attr( get_post_meta( $id, '_wp_attachment_image_alt', true ) ) . '" class="wp-image-' . absint( $id ) . '"/></figure><!-- /wp:image -->';
}
function gorizont_demo_columns( $items ) {
    $html = '<!-- wp:columns {"align":"wide"} --><div class="wp-block-columns alignwide">';
    foreach ( $items as $item ) { $html .= '<!-- wp:column --><div class="wp-block-column">' . $item . '</div><!-- /wp:column -->'; }
    return $html . '</div><!-- /wp:columns -->';
}
function gorizont_demo_insert( $type, $slug, $title, $content, $excerpt = '', $order = 0 ) {
    $existing = get_page_by_path( $slug, OBJECT, $type );
    if ( $existing ) { return array( $existing->ID, false ); }
    $id = wp_insert_post( array( 'post_type' => $type, 'post_name' => $slug, 'post_title' => $title, 'post_content' => $content, 'post_excerpt' => $excerpt, 'post_status' => 'publish', 'menu_order' => $order, 'meta_input' => array( '_gorizont_demo' => '1' ) ), true );
    if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
    return array( $id, true );
}
function gorizont_demo_media( $file, $title, $alt ) {
    $existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_gorizont_asset', 'meta_value' => $file, 'fields' => 'ids', 'posts_per_page' => 1 ) );
    if ( $existing ) { return $existing[0]; }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $tmp = wp_tempnam( $file );
    if ( ! $tmp || ! copy( GORIZONT_CORE_DIR . 'assets/' . $file, $tmp ) ) { throw new RuntimeException( 'Не удалось скопировать демоизображение: ' . $file ); }
    $id = media_handle_sideload( array( 'name' => $file, 'tmp_name' => $tmp ), 0, $title );
    if ( is_wp_error( $id ) ) { wp_delete_file( $tmp ); throw new RuntimeException( $id->get_error_message() ); }
    update_post_meta( $id, '_gorizont_asset', $file );
    update_post_meta( $id, '_wp_attachment_image_alt', $alt );
    return $id;
}
function gorizont_seed_demo( $configure = false ) {
    if ( ! add_option( 'gorizont_demo_lock', time(), '', false ) ) {
        $lock = (int) get_option( 'gorizont_demo_lock' );
        if ( $lock > time() - 600 ) { return new WP_Error( 'locked', 'Импорт уже выполняется. Повторите позже.' ); }
        delete_option( 'gorizont_demo_lock' );
        return new WP_Error( 'expired', 'Устаревшая блокировка снята. Повторите запуск.' );
    }
    try {
        $office = gorizont_demo_media( 'office.jpg', 'Рабочее пространство студии — иллюстрация', 'Светлое рабочее пространство с большими окнами и растениями' );
        $preview = array();
        foreach ( array( 'atelier', 'forma', 'sever' ) as $name ) { $preview[ $name ] = gorizont_demo_media( $name . '.webp', 'Демонстрационная концепция — ' . $name, 'Демонстрационный дизайн сайта «' . $name . '»' ); }
        $services = array(
            array( 'strategy', 'Стратегия и структура', 'Находим задачу бизнеса, изучаем аудиторию и собираем логику будущего сайта.', 'Обсуждаем продукт и путь клиента. Готовим структуру страниц, прототипы и требования к содержимому.', 'Стратегия' ),
            array( 'design', 'Дизайн с характером', 'Создаём цельную визуальную систему, которая делает ваш бренд узнаваемым.', 'Прорабатываем типографику, композицию, компоненты и адаптивные состояния. Согласуем ключевые страницы до начала разработки.', 'Дизайн' ),
            array( 'development', 'Разработка на WordPress', 'Превращаем дизайн в быстрый сайт с удобным управлением содержимым.', 'Разрабатываем собственную тему и блоки Gutenberg. Подключаем аналитику по согласованию, проверяем адаптивность и передаём инструкции.', 'Разработка' ),
            array( 'support', 'Развитие и поддержка', 'Помогаем сайту оставаться актуальным после запуска.', 'Планируем обновления, резервное копирование и развитие новых разделов. Состав работ и регламент фиксируются отдельно.', 'Поддержка' )
        );
        foreach ( $services as $index => $s ) {
            list( $id, $created ) = gorizont_demo_insert( 'g_service', $s[0], $s[1], gorizont_demo_h( 'Что входит в работу' ) . gorizont_demo_p( $s[3] ) . gorizont_demo_h( 'Начнём с вашей задачи' ) . gorizont_demo_p( 'Расскажите о проекте: обсудим подход, сроки и состав команды.' ) . gorizont_demo_buttons( array( array( 'Обсудить проект ↗', '/kontakty/' ) ) ), $s[2], $index + 1 );
            if ( $created ) { update_post_meta( $id, 'gorizont_price', 'Стоимость по запросу' ); update_post_meta( $id, 'gorizont_duration', 'После оценки задачи' ); wp_set_object_terms( $id, $s[4], 'g_service_type' ); }
        }
        $projects = array(
            array( 'atelier', 'Ателье / предметный дизайн', 'Светлая редакционная концепция для бренда предметов интерьера.', 'Корпоративные сайты', 'Корпоративный сайт' ),
            array( 'forma', 'Форма / цифровой продукт', 'Чёткая иерархия и выразительная типографика для цифрового сервиса.', 'Лендинги', 'Лендинг' ),
            array( 'sever', 'Север / пространство отдыха', 'Спокойная визуальная история о путешествии и впечатлениях.', 'Корпоративные сайты', 'Корпоративный сайт' )
        );
        foreach ( $projects as $index => $p ) {
            list( $id, $created ) = gorizont_demo_insert( 'g_project', $p[0], $p[1], gorizont_demo_p( 'Демонстрационный проект. Названия вымышлены; концепция не является выполненным заказом.', 'g-note' ) . gorizont_demo_h( 'Задача' ) . gorizont_demo_p( $p[2] ) . gorizont_demo_h( 'Решение' ) . gorizont_demo_p( 'Единая сетка, последовательная типографика и ясная навигация. Содержимое организовано так, чтобы его можно было обновлять без участия разработчика.' ) . gorizont_demo_h( 'Подход к реализации' ) . gorizont_demo_p( 'Адаптивные компоненты, оптимизированные изображения и собственная тема WordPress. При реальном заказе здесь размещаются согласованные результаты и материалы клиента.' ) . gorizont_demo_buttons( array( array( 'Хочу обсудить похожую задачу ↗', '/kontakty/' ) ) ), $p[2], $index + 1 );
            if ( $created ) { set_post_thumbnail( $id, $preview[ $p[0] ] ); update_post_meta( $id, 'gorizont_location', $p[4] . ' · концепция' ); update_post_meta( $id, 'gorizont_area', 'Дизайн и разработка' ); update_post_meta( $id, 'gorizont_year', 'Демонстрация' ); wp_set_object_terms( $id, $p[3], 'g_project_type' ); }
        }
        $hero = gorizont_demo_group( gorizont_demo_p( 'Независимая веб-студия · ГОРИЗОНТ', 'eyebrow hero-topline' ) . gorizont_demo_h( 'Сайты, которые открывают <strong>новые горизонты.</strong>', 1, 'hero' ) . gorizont_demo_group( gorizont_demo_p( 'Соединяем стратегию, выразительный дизайн и точную разработку. Создаём сайты для бизнеса, которому важно большее.', 'hero-intro' ) . gorizont_demo_buttons( array( array( 'Обсудить проект ↗', '/kontakty/' ), array( 'Смотреть проекты', '/proekty/', true ) ) ), 'hero-bottom' ) . gorizont_demo_image( $preview['atelier'], 'hero-image' ) . gorizont_demo_p( '01 / АТЕЛЬЕ — демонстрационная концепция сайта', 'g-note' ), 'hero' );
        $work = gorizont_demo_group( gorizont_demo_p( '01 / Избранное', 'eyebrow' ) . gorizont_demo_h( 'Каждая задача — новая перспектива.' ) . gorizont_demo_p( 'Три вымышленных проекта показывают подход к композиции, типографике и работе с содержимым.', 'g-note' ) . '<!-- wp:gorizont/collection {"kind":"g_project","count":3,"align":"wide"} /-->' . gorizont_demo_buttons( array( array( 'Все проекты ↗', '/proekty/', true ) ) ), 'section-space' );
        $service_block = gorizont_demo_group( gorizont_demo_p( '02 / Что мы делаем', 'eyebrow' ) . gorizont_demo_h( 'От первого вопроса до уверенного запуска.' ) . '<!-- wp:gorizont/collection {"kind":"g_service","count":3,"align":"wide"} /-->' . gorizont_demo_buttons( array( array( 'Все услуги ↗', '/uslugi/', true ) ) ), 'section-space' );
        $about_intro = gorizont_demo_group( gorizont_demo_p( '03 / Подход', 'eyebrow' ) . gorizont_demo_h( 'Хороший сайт начинается с понимания.' ) . gorizont_demo_p( 'Нам важны смысл, ясность и внимание к деталям. Мы проектируем не отдельные экраны, а цельный опыт — от первого впечатления до обращения в компанию.' ) . gorizont_demo_buttons( array( array( 'Больше о студии ↗', '/o-studii/', true ) ) ), 'statement' );
        $cta = gorizont_demo_group( gorizont_demo_p( 'Следующий горизонт — ваш', 'eyebrow' ) . gorizont_demo_h( 'Давайте создадим что-то значимое.' ) . gorizont_demo_p( 'Расскажите о своём бизнесе и задаче. Начнём со знакомства.' ) . gorizont_demo_buttons( array( array( 'Начать разговор ↗', '/kontakty/' ) ) ), 'contact-band section-space' );
        list( $home, $home_created ) = gorizont_demo_insert( 'page', 'glavnaya', 'Главная', $hero . $work . $service_block . $about_intro . $cta, 'Веб-студия ГОРИЗОНТ. Стратегия, дизайн и собственная разработка на WordPress.' );
        list( $service_page ) = gorizont_demo_insert( 'page', 'uslugi', 'Услуги', gorizont_demo_p( 'Поможем пройти весь путь от идеи до запуска — или подключимся к конкретному этапу.' ) . '<!-- wp:gorizont/collection {"kind":"g_service","count":12,"filters":true,"pagination":true,"align":"wide"} /-->' . $cta );
        list( $project_page ) = gorizont_demo_insert( 'page', 'proekty', 'Проекты', gorizont_demo_p( 'Демонстрационные концепции. Это примеры оформления, а не реальные клиентские заказы.', 'g-note' ) . '<!-- wp:gorizont/collection {"kind":"g_project","count":6,"filters":true,"pagination":true,"align":"wide"} /-->' . $cta );
        list( $about ) = gorizont_demo_insert( 'page', 'o-studii', 'Студия с широким взглядом', gorizont_demo_p( 'ГОРИЗОНТ — веб-студия для тех, кто ценит ясность, характер и качество исполнения.' ) . gorizont_demo_image( $office ) . gorizont_demo_p( 'Иллюстрация рабочего пространства. Демонстрационная фотография Unsplash.', 'g-note' ) . gorizont_demo_h( 'Сначала задача. Затем форма.' ) . gorizont_demo_p( 'Изучаем бизнес, предлагаем понятные решения и объясняем каждое из них. Согласуем этапы и показываем результат в процессе работы.' ) . gorizont_demo_h( 'Как строится работа' ) . gorizont_demo_columns( array(
            gorizont_demo_group( gorizont_demo_p( '01 /', 'eyebrow' ) . gorizont_demo_h( 'Понимаем', 3, 'large' ) . gorizont_demo_p( 'Знакомимся с командой, аудиторией и задачами проекта.' ), 'process-step', false ),
            gorizont_demo_group( gorizont_demo_p( '02 /', 'eyebrow' ) . gorizont_demo_h( 'Проектируем', 3, 'large' ) . gorizont_demo_p( 'Собираем структуру, дизайн-систему и прототипы.' ), 'process-step', false ),
            gorizont_demo_group( gorizont_demo_p( '03 /', 'eyebrow' ) . gorizont_demo_h( 'Запускаем', 3, 'large' ) . gorizont_demo_p( 'Разрабатываем, тестируем и обучаем работе с сайтом.' ), 'process-step', false )
        ) ) . $cta );
        list( $contacts ) = gorizont_demo_insert( 'page', 'kontakty', 'Начнём с разговора', gorizont_demo_p( 'Опишите задачу, расскажите о компании и желаемых сроках. Мы обсудим следующий шаг.' ) . '<!-- wp:gorizont/contacts {"align":"wide"} /-->' . gorizont_demo_p( 'Демонстрационные реквизиты: адрес example.com не предназначен для реальных обращений. Владелец сайта заменяет контакты в разделе «ГОРИЗОНТ».', 'g-note' ) );
        list( $privacy ) = gorizont_demo_insert( 'page', 'privacy', 'Политика конфиденциальности', gorizont_demo_p( 'Это демонстрационная заготовка политики. Перед публикацией владелец сайта указывает сведения об операторе, основания, цели и сроки обработки персональных данных, порядок обращений и используемые сервисы.' ) . gorizont_demo_h( 'Какие данные обрабатываются' ) . gorizont_demo_p( 'В этой версии нет форм заявок, сторонней аналитики и рекламных пикселей. Хостинг может вести технические журналы запросов. Контакты по почте или в мессенджере обрабатываются в соответствующих сервисах.' ) . gorizont_demo_h( 'Административная панель' ) . gorizont_demo_p( 'WordPress использует необходимые cookies для входа администраторов. При добавлении аналитики, форм или сторонних встраиваний актуализируйте политику.' ) );
        if ( add_option( 'gorizont_contacts', array( 'email' => 'hello@example.com', 'address' => 'Работаем по всей России · онлайн', 'hours' => 'Пн–Пт, 10:00–19:00 (МСК)' ) ) ) {
            add_option( 'gorizont_demo_contacts', 1 );
        }
        if ( $configure && $home_created && ! get_option( 'page_on_front' ) ) {
            update_option( 'show_on_front', 'page' ); update_option( 'page_on_front', $home );
        }
        if ( ! get_page_by_path( 'gorizont-main', OBJECT, 'wp_navigation' ) ) {
            $nav = '';
            foreach ( array( $project_page => 'Проекты', $service_page => 'Услуги', $about => 'О студии', $contacts => 'Обсудить проект ↗' ) as $id => $label ) {
                $nav .= '<!-- wp:navigation-link ' . wp_json_encode( array( 'label' => $label, 'type' => 'page', 'id' => $id, 'url' => get_permalink( $id ), 'kind' => 'post-type' ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ' /-->';
            }
            gorizont_demo_insert( 'wp_navigation', 'gorizont-main', 'Основное меню', $nav );
        }
        if ( $home_created ) { set_post_thumbnail( $home, $preview['atelier'] ); }
        flush_rewrite_rules();
        return array( 'home' => $home, 'message' => 'Демоматериалы готовы. Существующие записи не изменены.' );
    } catch ( Throwable $error ) { return new WP_Error( 'demo_failed', $error->getMessage() ); }
    finally { delete_option( 'gorizont_demo_lock' ); }
}
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'gorizont demo', function ( $args, $assoc ) {
        $result = gorizont_seed_demo( isset( $assoc['configure'] ) );
        if ( is_wp_error( $result ) ) { WP_CLI::error( $result->get_error_message() ); }
        WP_CLI::success( $result['message'] );
    } );
}
