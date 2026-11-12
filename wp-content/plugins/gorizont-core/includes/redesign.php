<?php
/** Светлая концепция 3.1 и явная миграция только демоматериалов. */
defined( 'ABSPATH' ) || exit;
function gorizont_v2_icon( $name ) {
    return '<!-- wp:image {"sizeSlug":"full","className":"benefit-icon"} --><figure class="wp-block-image size-full benefit-icon"><img src="' . esc_url( get_theme_file_uri( 'assets/icons/' . $name . '.svg' ) ) . '" alt=""/></figure><!-- /wp:image -->';
}
function gorizont_v2_cta() {
    return gorizont_demo_group( gorizont_demo_p( 'Новый проект начинается с разговора', 'eyebrow' ) . gorizont_demo_h( 'Обсудим ваш проект?' ) . gorizont_demo_p( 'Расскажите о задаче. Вместе найдём решение, которое будет работать на ваш бизнес.' ) . gorizont_demo_buttons( array( array( 'Обсудить проект', '/kontakty/' ) ) ), 'contact-band' );
}
function gorizont_v2_home( $image_id ) {
    $intro = gorizont_demo_p( 'Веб-студия «ГОРИЗОНТ»', 'eyebrow' ) . gorizont_demo_h( 'Сайты, которые <strong>двигают бизнес</strong>', 1, 'hero' ) . gorizont_demo_p( 'Проектируем и создаём современные сайты, сервисы и цифровые решения, которые привлекают клиентов, раскрывают характер бренда и помогают бизнесу расти.', 'hero-description' ) . gorizont_demo_buttons( array( array( 'Запустить проект', '/kontakty/' ), array( 'Смотреть работы', '/proekty/', true ) ) );
    $hero = gorizont_demo_columns( array( $intro, gorizont_demo_image( $image_id, 'hero-art' ) ), 'hero-layout' );
    $promises = gorizont_demo_columns( array(
        gorizont_demo_p( '<strong>От идеи до запуска</strong>', 'promise-title' ) . gorizont_demo_p( 'Одна команда. Цельный результат.' ),
        gorizont_demo_p( '<strong>Дизайн с характером</strong>', 'promise-title' ) . gorizont_demo_p( 'Узнаваемый образ вашего бренда.' ),
        gorizont_demo_p( '<strong>Удобное управление</strong>', 'promise-title' ) . gorizont_demo_p( 'Контент под вашим контролем.' ),
        gorizont_demo_p( '<strong>Развитие проекта</strong>', 'promise-title' ) . gorizont_demo_p( 'Сайт, готовый к новым задачам.' )
    ), 'promise-strip' );
    $hero = gorizont_demo_group( $hero . $promises, 'hero-v2' );
    $services = gorizont_demo_group( gorizont_demo_h( 'Наши услуги' ) . '<!-- wp:gorizont/collection {"kind":"g_service","count":5,"align":"wide"} /-->', 'section-v2' );
    $work = gorizont_demo_group( gorizont_demo_h( 'Избранные проекты' ) . '<!-- wp:gorizont/collection {"kind":"g_project","count":3,"align":"wide"} /-->' . gorizont_demo_p( 'Демонстрационные концепции: показываем направления дизайна, не выдавая их за клиентские заказы.', 'g-note' ), 'section-v2' );
    $benefits = array(
        array( 'gem', 'Стратегия и аналитика', 'Начинаем с целей бизнеса и людей, для которых создаётся продукт.' ),
        array( 'layers', 'Современные технологии', 'Выбираем надёжные решения, подходящие именно вашему проекту.' ),
        array( 'users-round', 'Внимание к деталям', 'Соединяем дизайн, разработку и содержание в одном процессе.' ),
        array( 'infinity', 'Долгосрочный результат', 'Закладываем основу для развития сайта вместе с вашим бизнесом.' )
    );
    $columns = array();
    foreach ( $benefits as $b ) { $columns[] = gorizont_v2_icon( $b[0] ) . gorizont_demo_h( $b[1], 3, 'large' ) . gorizont_demo_p( $b[2] ); }
    return $hero . $services . $work . gorizont_demo_group( gorizont_demo_h( 'Почему выбирают «Горизонт»' ) . gorizont_demo_columns( $columns, 'benefit-grid' ), 'section-v2' ) . gorizont_v2_cta();
}
function gorizont_v2_apply() {
    $hero = gorizont_demo_media( 'horizon-hero.webp', 'ГОРИЗОНТ — цифровой восход', 'Авторская стеклянная композиция с оранжевым восходом над облаками' );
    $home = get_page_by_path( 'glavnaya' );
    if ( $home && get_post_meta( $home->ID, '_gorizont_demo', true ) ) {
        wp_update_post( array( 'ID' => $home->ID, 'post_content' => gorizont_v2_home( $hero ) ) );
        set_post_thumbnail( $home->ID, $hero );
    }
    $illustrations = gorizont_demo_media( 'service-illustrations.webp', 'ГОРИЗОНТ — иллюстрации услуг', 'Объёмные иллюстрации пяти цифровых направлений' );
    $services = array(
        array( 'corporate', 'Корпоративные сайты', 'Надёжные решения для вашего бизнеса и сильная презентация бренда.', 'Разработка сайтов' ),
        array( 'shop', 'Интернет-магазины', 'Удобные покупки, каталог и продуманный путь от выбора до заказа.', 'Интернет-магазины' ),
        array( 'webapps', 'Веб-сервисы', 'Цифровые продукты, личные кабинеты и автоматизация процессов.', 'Веб-сервисы' ),
        array( 'design', 'Дизайн интерфейсов', 'Выразительные и понятные интерфейсы с характером вашего бренда.', 'Дизайн' ),
        array( 'support', 'Развитие и поддержка', 'Обновление, продвижение и стабильная работа вашего сайта.', 'Поддержка' )
    );
    foreach ( $services as $index => $s ) {
        list( $id ) = gorizont_demo_insert( 'g_service', $s[0], $s[1], '', $s[2], $index + 1 );
        if ( get_post_meta( $id, '_gorizont_demo', true ) ) {
            wp_update_post( array( 'ID' => $id, 'post_title' => $s[1], 'post_excerpt' => $s[2], 'menu_order' => $index + 1, 'post_content' => gorizont_demo_h( 'Решение начинается с вашей задачи' ) . gorizont_demo_p( $s[2] ) . gorizont_demo_p( 'Изучаем ваш продукт и аудиторию, проектируем структуру, разрабатываем визуальную систему и проверяем результат на разных устройствах. Состав работ и сроки определяем после знакомства с проектом.' ) . gorizont_v2_cta() ) );
            set_post_thumbnail( $id, $illustrations );
            update_post_meta( $id, 'gorizont_price', 'После оценки задачи' );
            update_post_meta( $id, 'gorizont_duration', 'Индивидуально для проекта' );
            wp_set_object_terms( $id, $s[3], 'g_service_type' );
        }
    }
    foreach ( array( 'strategy', 'development' ) as $slug ) {
        $post = get_page_by_path( $slug, OBJECT, 'g_service' );
        if ( $post && get_post_meta( $post->ID, '_gorizont_demo', true ) ) { wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'draft' ) ); }
    }
    $projects = array(
        array( 'atelier', 'Премиум-авто онлайн', 'Интернет-магазин', 'Автомобили. Выбор. Характер.', 'auto.jpg', 'Интернет-магазины' ),
        array( 'forma', 'Современные жилые комплексы', 'Корпоративный сайт', 'Архитектура новой жизни.', 'estate.jpg', 'Корпоративные сайты' ),
        array( 'sever', 'Фитнес-клуб нового формата', 'Веб-платформа', 'Энергия движения.', 'fitness.jpg', 'Лендинги' )
    );
    foreach ( $projects as $index => $p ) {
        $post = get_page_by_path( $p[0], OBJECT, 'g_project' );
        if ( ! $post || ! get_post_meta( $post->ID, '_gorizont_demo', true ) ) { continue; }
        $image = gorizont_demo_media( $p[4], $p[1] . ' — иллюстрация концепции', $p[1] );
        wp_update_post( array( 'ID' => $post->ID, 'post_title' => $p[1], 'post_excerpt' => $p[3], 'menu_order' => $index + 1, 'post_content' => gorizont_demo_p( 'Демонстрационная концепция. Это направление дизайна, а не реализованный клиентский заказ.', 'g-note' ) . gorizont_demo_h( 'Сильное первое впечатление. Продуманный опыт.' ) . gorizont_demo_p( 'Концепция объединяет крупную типографику, выразительную фотографию и ясную структуру. Основная задача — помочь посетителю быстро понять продукт и сделать следующий шаг.' ) . gorizont_demo_h( 'От визуальной идеи до работающего сайта' ) . gorizont_demo_p( 'В реальном проекте здесь появятся согласованные макеты, описание процесса и подтверждённые результаты. Все материалы можно редактировать в WordPress.' ) . gorizont_v2_cta() ) );
        set_post_thumbnail( $post->ID, $image );
        update_post_meta( $post->ID, 'gorizont_location', $p[2] . ' · концепция' );
        wp_set_object_terms( $post->ID, $p[5], 'g_project_type' );
    }
    foreach ( get_posts( array( 'post_type' => array( 'page', 'g_service', 'g_project', 'wp_navigation' ), 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => '_gorizont_demo', 'meta_value' => '1' ) ) as $post ) {
        $content = str_replace( array( ' ↗', ' ↘', '↗', '↘' ), '', $post->post_content );
        if ( $content !== $post->post_content ) { wp_update_post( array( 'ID' => $post->ID, 'post_content' => $content ) ); }
    }
    $contact = get_page_by_path( 'kontakty' );
    if ( $contact && get_post_meta( $contact->ID, '_gorizont_demo', true ) ) {
        wp_update_post( array( 'ID' => $contact->ID, 'post_content' => gorizont_demo_p( 'Расскажите о компании, продукте и задаче. Начнём с правильных вопросов.' ) . '<!-- wp:gorizont/brief /-->' . gorizont_demo_h( 'Где мы работаем' ) . '<!-- wp:gorizont/contacts /-->' ) );
    }
    $privacy = get_page_by_path( 'privacy' );
    if ( $privacy && get_post_meta( $privacy->ID, '_gorizont_demo', true ) ) {
        $updated = str_replace( 'В этой версии нет форм заявок, сторонней аналитики и рекламных пикселей. Хостинг может вести технические журналы запросов. Контакты по почте или в мессенджере обрабатываются в соответствующих сервисах.', 'Бриф заполняется и скачивается в вашем браузере. Введённые в него данные не передаются на сервер. Сторонняя аналитика и рекламные пиксели не подключены. Хостинг может вести технические журналы запросов.', $privacy->post_content );
        if ( $updated !== $privacy->post_content ) { wp_update_post( array( 'ID' => $privacy->ID, 'post_content' => $updated ) ); }
    }
    return $hero;
}
function gorizont_brief_block() {
    return '<div ' . get_block_wrapper_attributes( array( 'class' => 'g-brief' ) ) . '><p class="g-note">Составьте и скачайте бриф проекта. В демонстрационной версии данные сохраняются только у вас; отправка заявки не выполняется.</p><form class="g-brief-form"><div class="g-form-row"><label>Как вас зовут<input name="name" autocomplete="name" required placeholder="Ваше имя"></label><label>Электронная почта<input name="email" type="email" autocomplete="email" required placeholder="name@company.ru"></label></div><label>Что планируете создать<select name="service"><option>Корпоративный сайт</option><option>Интернет-магазин</option><option>Веб-сервис</option><option>Дизайн интерфейса</option><option>Другое</option></select></label><label>Расскажите о задаче<textarea name="message" rows="5" minlength="10" required placeholder="О бизнесе, целях и сроках проекта"></textarea></label><button type="submit" class="wp-block-button__link wp-element-button">Скачать бриф проекта</button><p role="status" aria-live="polite"></p></form></div>';
}
add_action( 'init', function () { register_block_type( 'gorizont/brief', array( 'api_version' => 3, 'editor_script' => 'gorizont-editor', 'render_callback' => 'gorizont_brief_block' ) ); } );
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'gorizont redesign', function () {
        $result = gorizont_v2_apply();
        WP_CLI::success( 'Согласованная светлая концепция применена только к демоматериалам. Ревизии страниц сохранены.' );
    } );
}
