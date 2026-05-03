<?php

$options = [
	'content'  => [
		'label' => __( 'Content', 'update-urls' ),
		'desc'  => __( 'Search in page content (posts, pages, custom post types, revisions)', 'update-urls' ),
	],
	'excerpts' => [
		'label' => __( 'Excerpts', 'update-urls' ),
		'desc'  => __( 'Search in excerpts', 'update-urls' ),
	],

	'attachments' => [
		'label' => __( 'Attachments', 'update-urls' ),
		'desc'  => __( 'Search in attachments (images, documents, general media)', 'update-urls' ),
	],

	'links' => [
		'label' => __( 'Links', 'update-urls' ),
		'desc'  => __( 'Search in links', 'update-urls' ),
	],

	'custom' => [
		'label' => __( 'Custom', 'update-urls' ),
		'desc'  => __( 'Search in custom fields and meta boxes', 'update-urls' ),
	],
];

$additional_settings = [
//	'case_insensitive' => [
//		'label' => __( 'Case-Insensitive', 'update-urls' ),
//		'desc'  => __( 'Searches are case-sensitive by default.', 'update-urls' ),
//	],

	'guids' => [
		'label' => __( 'Replace GUIDs', 'update-urls' ),
		'desc'  => sprintf( __( 'Update ALL GUIDs GUIDs for posts should only be changed on development sites. <a href="%s" target="_blank">Learn More</a>.',
			'update-urls' ), 'http://codex.wordpress.org/Changing_The_Site_URL#Important_GUID_Note' ),
	],

//	'dry_run' => [
//		'label' => __( 'Run as dry run', 'update-urls' ),
//		'desc'  => __( 'If checked, no changes will be made to the database, allowing you to check the results beforehand.', 'update-urls' ),
//	],
];

if ( isset( $_POST['kc_uu_settings_submit'] ) && ! check_admin_referer( 'kc_uu_submit', 'kc_uu_nonce' ) ) {
	if ( isset( $_POST['search_for'] ) && isset( $_POST['replace_with'] ) ) {
		$search_for = esc_url_raw( wp_unslash( $_POST['search_for'] ) );
		$replace_with = esc_url_raw( wp_unslash( $_POST['replace_with'] ) );
	}
	echo '<div id="message" class="error fade"><p><strong>' . esc_html__( 'ERROR',
			'update-urls' ) . ' - ' . esc_html__( 'Please try again.', 'update-urls' ) . '</strong></p></div>';
} elseif ( isset( $_POST['kc_uu_settings_submit'] ) && ! isset( $_POST['kc_uu_update_links'] ) ) {
	if ( isset( $_POST['search_for'] ) && isset( $_POST['replace_with'] ) ) {
		$search_for = esc_url_raw( wp_unslash( $_POST['search_for'] ) );
		$replace_with = esc_url_raw( wp_unslash( $_POST['replace_with'] ) );
	}
	echo '<div id="message" class="error fade"><p><strong>' . esc_html__( 'ERROR',
			'update-urls' ) . ' - ' . esc_html__( 'Your URLs have not been updated.',
			'update-urls' ) . '</p></strong><p>' . esc_html__( 'Please select at least one checkbox.',
			'update-urls' ) . '</p></div>';
}
elseif ( isset( $_POST['kc_uu_settings_submit'] ) ) {

$kc_uu_update_links = isset( $_POST['kc_uu_update_links'] ) ? (array) $_POST['kc_uu_update_links'] : [];

$kc_uu_update_links = array_map( 'esc_attr', $kc_uu_update_links );

if ( isset( $_POST['search_for'] ) && isset( $_POST['replace_with'] ) ) {
	$search_for = esc_url_raw( wp_unslash( $_POST['search_for'] ) );
	$replace_with = esc_url_raw( wp_unslash( $_POST['replace_with'] ) );
}
if ( ( $search_for && $search_for != 'http://www.oldurl.com' && trim( $search_for ) != '' ) && ( $replace_with && $replace_with != 'http://www.newurl.com' && trim( $replace_with ) != '' ) ) {
$results = \KaizenCoders\UpdateURLS\Helper::UpdateURLS( $kc_uu_update_links, $search_for, $replace_with );


$empty       = true;
$emptystring = '<strong>' . __( 'Why do the results show 0 URLs updated?',
		'update-urls' ) . '</strong><br/>' . __( 'This happens if a URL is incorrect OR if it is not found in the content. Check your URLs and try again.',
		'update-urls' );

$resultstring = '';
foreach ( $results as $result ) {
	$empty        = ( $result[0] != 0 || $empty == false ) ? false : true;
	$resultstring .= '<br/><strong>' . $result[0] . '</strong> ' . $result[1];
}

if ( $empty ) :
?>
<div id="message" class="error fade">
    <table>
        <tr>
            <td><p><strong>
						<?php _e( 'ERROR: Something may have gone wrong.', 'update-urls' ); ?>
                    </strong><br/>
					<?php _e( 'No search found.', 'update-urls' ); ?>
                </p>
				<?php
				else :
				?>
                <div id="message" class="updated fade">
                    <table>
                        <tr>
                            <td><p><strong>
										<?php _e( 'Success! data have been updated.', 'update-urls' ); ?>
                                    </strong></p>
								<?php
								endif;
								?>
                                <p><u>
										<?php _e( 'Results', 'update-urls' ); ?>
                                    </u><?php echo $resultstring; ?></p>
								<?php echo ( $empty ) ? '<p>' . $emptystring . '</p>' : ''; ?></td>
                            <td width="60"></td>
                            <td align="center"><?php if ( ! $empty ) : ?>
                                    <p>
									<?php // You can now uninstall this plugin.<br/> ?>
								<?php endif; ?></td>
                        </tr>
                    </table>
                </div>
				<?php
				} else {
					echo '<div id="message" class="error fade"><p><strong>' . esc_html__( 'ERROR',
							'update-urls' ) . ' - ' . esc_html__( 'Your data have not been updated.',
							'update-urls' ) . '</p></strong><p>' . esc_html_e( 'Please enter values for both search for and replace with.',
							'update-urls' ) . '</p></div>';
				}
				}
				?>


                <div class="bg-white">
                    <div class=" flex flex-auto">

                        <form method="post" action="" class="p-10 min-h-full">
							<?php wp_nonce_field( 'kc_uu_submit', 'kc_uu_nonce' ); ?>

                            <!-- Important Notice -->
                            <div class="section bg-gray-100 p-5 mb-5 border-2">
                                <p class="text-xl bold-text text-center mb-5 underline">Important Note</p>
                                <ul>
                                    <li>
                                    <li class="text-red-500 bold-text">
                                        👉 <?php esc_html_e( 'WE RECOMMEND THAT YOU BACKUP YOUR WEBSITE.',
											'update-urls' ); ?></li>
                                    <li>
                                        👉 <?php esc_html_e( 'You may need to restore it if incorrect data are entered in the fields below.',
											'update-urls' ); ?></li>
                                </ul>
                            </div>

                            <!-- Search / Replace -->
                            <div class="grid grid-cols-1 gap-x-8 gap-y-10 border-b border-gray-900/10 pb-12 m-5 md:grid-cols-4">
                                <div>
                                    <h2 class="text-base font-semibold leading-7 text-gray-900"><?php esc_html_e( 'Search / Replace',
											'update-urls' ); ?></h2>
                                    <p class="mt-1 text-sm leading-6 text-gray-600"></p>
                                </div>

                                <div class="grid grid-cols max-w-2xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6 md:col-span-2">
                                    <div class="sm:col-span-3">
                                        <label for="last-name"
                                               class="block text-sm font-medium leading-6 text-gray-900">
											<?php esc_html_e( 'Search For', 'update-urls' ); ?>
                                        </label>
                                        <div class="mt-2">
                                            <input id=""
                                                   class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                   placeholder=""
                                                   name="search_for"
                                                   value=""
                                                   size="30" maxlength="100"/>
                                        </div>
                                    </div>

                                    <div class="sm:col-span-3">
                                        <label for="last-name"
                                               class="block text-sm font-medium leading-6 text-gray-900">
											<?php esc_html_e( 'Replace With', 'update-urls' ); ?>
                                        </label>
                                        <div class="mt-2">
                                            <input id=""
                                                   class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                   placeholder=""
                                                   name="replace_with"
                                                   value=""
                                                   size="30" maxlength="100"/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Where To Update -->
                            <div class="grid grid-cols-1 gap-x-8 gap-y-10 border-b border-gray-900/10 pb-12 md:grid-cols-4 m-5">
                                <div class="">
                                    <h2 class="text-base font-semibold leading-7 text-gray-900"><?php esc_html_e( 'Where to Update?',
											'update-urls' ); ?></h2>
                                    <p class="mt-1 text-sm leading-6 text-gray-600"></p>
                                </div>

                                <div class="max-w-2xl space-y-10 md:col-span-2">
                                    <fieldset>
                                        <div class="space-y-6">
											<?php foreach ( $options as $key => $option ) { ?>
                                                <div class="relative flex gap-x-3">
                                                    <div class="flex h-8 items-center">
                                                        <input id="<?php echo $key; ?>" name="kc_uu_update_links[]"
                                                               type="checkbox"
                                                               class="h-4 w-4 form-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                                               value="<?php echo $key; ?>"/>
                                                    </div>
                                                    <div class="text-sm leading-6">
                                                        <label for="<?php echo $key; ?>"
                                                               class="font-medium text-gray-900"><?php echo $option['label'] ?></label>
                                                        <p class="text-gray-500"><?php echo $option['desc']; ?></p>
                                                    </div>
                                                </div>
											<?php } ?>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>

                            <!-- Additional Settings -->
                            <div class="grid grid-cols-1 gap-x-8 gap-y-10 border-b border-gray-900/10 pb-12 md:grid-cols-4 m-5">
                                <div class="">
                                    <h2 class="text-base font-semibold leading-7 text-gray-900"><?php esc_html_e( 'Additional Settings',
											'update-urls' ); ?></h2>
                                    <p class="mt-1 text-sm leading-6 text-gray-600"></p>
                                </div>

                                <div class="max-w-2xl space-y-10 md:col-span-2">
                                    <fieldset>
                                        <div class="space-y-6">
											<?php foreach ( $additional_settings as $key => $option ) { ?>
                                                <div class="relative flex gap-x-3">
                                                    <div class="flex h-8 items-center">
                                                        <input id="<?php echo $key; ?>" name="kc_uu_update_links[]"
                                                               type="checkbox"
                                                               class="h-4 w-4 form-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                                                               value="<?php echo $key; ?>"/>
                                                    </div>
                                                    <div class="text-sm leading-6">
                                                        <label for="<?php echo $key; ?>"
                                                               class="font-medium text-gray-900"><?php echo $option['label'] ?></label>
                                                        <p class="text-gray-500"><?php echo $option['desc']; ?></p>
                                                    </div>
                                                </div>
											<?php } ?>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>

                            <div class="flex flex-row border-b border-gray-100 mt-10">
                                <div class="flex w-1/5">
                                    <div class="ml-4">
                                        <input class="button-primary" name="kc_uu_settings_submit"
                                               value="<?php esc_attr_e( 'Update URLs NOW', 'update-urls' ); ?>"
                                               type="submit"/>
                                    </div>
                                </div>
                            </div>
                        </form>






