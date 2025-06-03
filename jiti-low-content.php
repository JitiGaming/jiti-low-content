<?php
/*
Plugin Name: Jiti - Low Content
Description: Affiche la liste des articles dont le nombre de mots est inférieur ou supérieur à un seuil défini, avec recherche par mot-clé dans le titre.
Version: 1.3.1
Author: Jiti
Author URI: https://jiti.me
License: Copyleft
*/

add_action('admin_menu', function() {
    add_menu_page(
        'Jiti - Low Content',
        'Articles/Mots',
        'manage_options',
        'jiti-low-content', // Slug modifié ici
        'jiti_low_content_admin_page',
        'dashicons-editor-unlink'
    );
});

function jiti_low_content_admin_page() {
    // Initialisation sécurisée des variables
    $threshold = 300;
    $type = 'inf';
    $keyword = '';

    // Traitement du formulaire sécurisé avec nonce et vérification de la capacité utilisateur
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (
            isset($_POST['jiti_low_content_nonce']) &&
            wp_verify_nonce($_POST['jiti_low_content_nonce'], 'jiti_low_content_filter') &&
            current_user_can('manage_options')
        ) {
            $threshold = isset($_POST['threshold']) ? intval($_POST['threshold']) : 300;
            $type = (isset($_POST['type']) && in_array($_POST['type'], ['inf', 'sup'])) ? $_POST['type'] : 'inf';
            $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        }
    }
    ?>
    <div class="wrap">
        <h1>Jiti - Low Content</h1>
        <form method="post" style="display: flex; align-items: center; gap: 10px;">
            <?php wp_nonce_field('jiti_low_content_filter', 'jiti_low_content_nonce'); ?>
            <label for="type">Filtrer&nbsp;:</label>
            <select name="type" id="type">
                <option value="inf" <?php selected($type, 'inf'); ?>>Inférieur à</option>
                <option value="sup" <?php selected($type, 'sup'); ?>>Supérieur à</option>
            </select>
            <label for="threshold">Nombre de mots&nbsp;:</label>
            <input type="number" name="threshold" id="threshold" value="<?php echo esc_attr($threshold); ?>" min="0">
            <label for="keyword">Mot-clé&nbsp;:</label>
            <input type="text" name="keyword" id="keyword" value="<?php echo esc_attr($keyword); ?>">
            <input type="submit" class="button button-primary" value="Afficher">
        </form>
        <hr>
        <?php
        $args = [
            'post_type' => 'post',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ];
        $query = new WP_Query($args);
        $count = 0;
        ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Nombre de mots</th>
                    <th>Lien public</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($query->have_posts()) :
                    while ($query->have_posts()) : $query->the_post();
                        $content = get_post_field('post_content', get_the_ID());
                        $word_count = str_word_count(strip_tags($content));
                        $title = get_the_title();
                        $title_match = true;
                        if ($keyword !== '') {
                            $title_match = stripos($title, $keyword) !== false;
                        }
                        $show = ($type === 'inf') ? ($word_count < $threshold) : ($word_count > $threshold);
                        $show = $show && $title_match;
                        if ($show) :
                            $count++;
                            $edit_link = get_edit_post_link(get_the_ID());
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url($edit_link); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </td>
                                <td><?php echo $word_count; ?></td>
                                <td><a href="<?php the_permalink(); ?>" target="_blank">Voir</a></td>
                            </tr>
                            <?php
                        endif;
                    endwhile;
                    wp_reset_postdata();
                endif;

                if ($count === 0) {
                    echo '<tr><td colspan="3">Aucun article trouvé.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <p><?php echo $count; ?> article<?php echo ($count > 1) ? 's' : ''; ?> trouvé<?php echo ($count > 1) ? 's' : ''; ?>.</p>
    </div>
    <?php
}
