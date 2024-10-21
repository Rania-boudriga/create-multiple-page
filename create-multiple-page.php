<?php
/*
Plugin Name: Create Multiple Pages by Rania
Description: A plugin that allows the creation of multiple pages via a repeater form in the backend with a field for cities.
Version: 1.0
Author: Rania
*/

// Add a menu in the WordPress admin
add_action('admin_menu', 'cmp_admin_menu');
function cmp_admin_menu() {
    add_menu_page(
        'Create Multiple Pages',         // Page title
        'Create Multiple Pages',         // Menu title
        'manage_options',                // Required capability
        'create-multiple-page',          // Page slug
        'cmp_form_page',                 // Function to call to display content
        'dashicons-menu'                 // Dashicons icon
    );
}

// Enqueue scripts and styles for the repeater
add_action('admin_enqueue_scripts', 'cmp_enqueue_admin_scripts');
function cmp_enqueue_admin_scripts() {
    wp_enqueue_style('cmp-admin-style', plugins_url('assets/css/style.css', __FILE__));
    wp_enqueue_script('cmp-admin-script', plugins_url('assets/js/script.js', __FILE__), array('jquery'), null, true);
}

function enqueue_custom_styles() {
    wp_enqueue_style('custom-style', plugins_url('assets/css/main.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'enqueue_custom_styles');

function enqueue_bootstrap() {
    // Enqueue Bootstrap CSS
    wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');

    // Enqueue jQuery (if not already included)
    wp_enqueue_script('jquery');

    // Enqueue Bootstrap JS
    wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_bootstrap');

// Retrieve available cities
function get_cities() {
    return array(
        'Paris 1' => 'Paris 1',
        'Paris 2' => 'Paris 2',
        'Lyon' => 'Lyon',
        'Marseille' => 'Marseille',
        'Toulouse' => 'Toulouse',
        // Add other cities as needed
    );
}

// Display the repeater form in the admin
function cmp_form_page() {
    ?>
    <div class="wrap">
        <h2>Create Multiple Pages</h2>
        <form method="post" id="cmp-pages-form">
            <div id="repeater-container">
                <div class="page-form">
                    <h3>New Page</h3>
                    <label for="title_0">Title:</label></br>
                    <input type="text" name="pages[0][title]" id="title_0" required /><br><br>

                    <label for="description_0">Description:</label></br>
                    <textarea name="pages[0][description]" id="description_0" required></textarea><br><br>

                    <label for="excerpt_0">Excerpt:</label></br>
                    <textarea name="pages[0][excerpt]" id="excerpt_0"></textarea><br><br>

                    <label for="cities_0">Cities:</label></br>

                    <select class="list-box" name="pages[0][cities][]" id="cities_0" multiple required>
                        <?php
                        $cities = get_cities();
                        foreach ($cities as $value => $label) {
                            echo "<option value=\"$value\">$label</option>";
                        }
                        ?>
                    </select><br><br>
                </div>
            </div>

            <button type="button" id="add-page-form" class="button">Add Another Page</button><br><br>
            <input type="submit" name="submit" value="Create Pages" class="button button-primary" />
        </form>
    </div>
    <?php

    // Call the page creation function upon form submission
    if (isset($_POST['submit'])) {
        cmp_create_pages($_POST['pages']);
    }
}

// Create pages from the repeater form data
function cmp_create_pages($pages_data) {
    foreach ($pages_data as $page_index => $page) {
        $title = sanitize_text_field($page['title']);
        $description = sanitize_textarea_field($page['description']);
        $excerpt = sanitize_textarea_field($page['excerpt']);
        $cities = isset($page['cities']) ? array_map('sanitize_text_field', $page['cities']) : [];

        // Create the page
        $page_data = array(
            'post_title'    => $title,
            'post_content'  => $description, // Main content
            'post_excerpt'  => $excerpt,
            'post_status'   => 'publish',
            'post_type'     => 'page',
        );

        // Insert the page and retrieve the ID
        $page_id = wp_insert_post($page_data);

        // Add a custom field for the cities
        if ($page_id) {
            if (!empty($cities)) {
                add_post_meta($page_id, 'selected_cities', $cities); // Save cities as an array
            }
        }
    }
}

// Add a Meta Box to display selected cities in the page editor only for plugin-created pages
add_action('add_meta_boxes', 'cmp_add_cities_meta_box');
function cmp_add_cities_meta_box() {
    add_meta_box(
        'cmp_cities_meta_box',
        'List of French cities:',
        'cmp_render_cities_meta_box',
        'page', // Content type
        'normal',  // Change context to 'normal'
        'high' // High priority to appear at the top
    );
}

// Render the Meta Box only if the page was created by the plugin
function cmp_render_cities_meta_box($post) {
    // Check if the 'selected_cities' meta exists for this post
    $cities = get_post_meta($post->ID, 'selected_cities', true);

    // If there are no cities, don't display the meta box
    if (empty($cities)) {
        echo '<p>This page does not have any cities selected or was not created by the plugin.</p>';
        return;
    }

    // Otherwise, show the meta box
    $all_cities = get_cities();
    ?>
    <select name="cmp_cities[]" id="cmp_cities" multiple style="width: 50%; height: 100px;">
        <?php
        foreach ($all_cities as $value => $label) {
            $selected = in_array($value, (array) $cities) ? 'selected' : '';
            echo "<option value=\"$value\" $selected>$label</option>";
        }
        ?>
    </select>
    <?php
}

// Save the selected cities upon page update
add_action('save_post', 'cmp_save_cities_meta_box');
function cmp_save_cities_meta_box($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (isset($_POST['cmp_cities'])) {
        $selected_cities = array_map('sanitize_text_field', $_POST['cmp_cities']);
        update_post_meta($post_id, 'selected_cities', $selected_cities);
    } else {
        delete_post_meta($post_id, 'selected_cities'); // Remove the field if no city is selected
    }
}
function display_pages_carousel() {
    // Initialize an empty string to store the output
    $output = '<div id="pagesCarousel" class="carousel slide" data-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <div class="row">'; // Start a row to hold four items

    // Get the ID of the current page
    $current_page_id = get_the_ID();

    // Query to retrieve all pages except the current one
    $args = array(
        'post_type' => 'page',
        'posts_per_page' => -1, // Get all pages
        'post_status' => 'publish', // Only published pages
        'post__not_in' => array($current_page_id), // Exclude the current page
    );

    $pages = new WP_Query($args);
    $counter = 0; // Counter to track the number of items

    // Check if there are any pages
    if ($pages->have_posts()) {
        while ($pages->have_posts()) {
            $pages->the_post();
            // Add each page to the output as a link
            $output .= '<div class="col-md-3">
                            <div class="card mb-4">
                                <div class="card-body text-center">
                                    <h5><a href="' . get_permalink() . '">' . get_the_title() . '</a></h5>
                                </div>
                            </div>
                        </div>';

            $counter++;

            // If we've added 4 items, start a new carousel item
            if ($counter % 4 == 0) {
                $output .= '</div></div>'; // Close the current row and item
                if ($counter < $pages->found_posts) { // Check if there are more items to display
                    $output .= '<div class="carousel-item">
                                    <div class="row">';
                }
            }
        }

        // Close the last item if there are leftover pages
        if ($counter % 4 != 0) {
            $output .= '</div></div>'; // Close the current row and item
        }
    } else {
        $output .= '<div class="carousel-item active">
                        <div class="row">
                            <div class="col text-center">
                                <h5>No other pages created.</h5>
                            </div>
                        </div>
                    </div>'; // Message if no other pages found
    }

    // Reset post data
    wp_reset_postdata();
    if ($counter % 4 == 0) {
        if ($counter < $pages->found_posts) { 
            $output .= '</div>
                    <a class="carousel-control-prev" href="#pagesCarousel" role="button" data-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="sr-only"><</span>
                        </a>
                        <a class="carousel-control-next" href="#pagesCarousel" role="button" data-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="sr-only">></span>
                        </a>
                    </div>';
        }
    }
    return $output; // Return the output for the shortcode
   
}

// Register the shortcode [pages_carousel]
add_shortcode('pages_carousel', 'display_pages_carousel');


