<?php
//check if have a new tag in BD
if($this->bar_post['tag'] != ""){
    $data = new Egoi_For_Wp();
    $info = $data->getTag($this->bar_post['tag']);
    $tag = $info['ID'];
}
else{
    $tag = $this->bar_post['tag-egoi'];
}
?>

<ul class="tab">
    <li class="tab-item active">
        <a href="#" tab-target="smsnf-configuration"><?= _e( 'Settings', 'egoi-for-wp' ); ?></a>
    </li>
    <li class="tab-item">
        <a href="#" tab-target="smsnf-appearance"><?= _e( 'Appearance', 'egoi-for-wp' ); ?></a>
    </li>
    <li class="tab-item">
        <a href="#" tab-target="smsnf-messages"><?= _e( 'Messages', 'egoi-for-wp' ); ?></a>
    </li>
</ul>

<form id="smsnf-subscriber-bar" method="post" name="bar_options" action="<?= admin_url('options.php'); ?>">
<?php
settings_fields(Egoi_For_Wp_Admin::BAR_OPTION_NAME);
settings_errors();
?>
    <div id="smsnf-configuration" class="smsnf-tab-content smsnf-grid active">
        <div>
            <div class="smsnf-input-group">
                <label for="enabled_bar"><?= _e( 'Enable Bar?', 'egoi-for-wp' ); ?></label>
                <p class="subtitle"><?= _e( 'A valid way to completely disable the bar.', 'egoi-for-wp' ); ?></p>
                <div class="form-group switch-yes-no">
                    <label class="form-switch">
                        <input id="enabled_bar" name="egoi_bar_sync[enabled]" value="1" <?php checked($this->bar_post['enabled'], 1); ?> type="checkbox">
                        <i class="form-icon"></i><div class="yes"><?= _e( 'Yes' ); ?></div><div class="no"><?= _e( 'No' ); ?></div>
                    </label>
                </div>
            </div>
            <div class="smsnf-input-group">
                <label for="bar_open"><?= _e( 'Open Bar by default?', 'egoi-for-wp' ); ?></label>
                <p class="subtitle"><?php _e( 'Show or Hide by default the bar.', 'egoi-for-wp' ); ?></p>
                <div class="form-group switch-yes-no">
                    <label class="form-switch">
                        <input id="bar_open" name="egoi_bar_sync[open]" value="1" <?php checked($this->bar_post['open'], 1); ?> type="checkbox">
                        <i class="form-icon"></i><div class="yes"><?= _e( 'Yes' ); ?></div><div class="no"><?= _e( 'No' ); ?></div>
                    </label>
                </div>
            </div>
            <!-- Double Opt-In -->
            <div class="smsnf-input-group">
                <label for="bar_double_optin"><?= _e( 'Enable Double Opt-In?', 'egoi-for-wp' ); ?></label>
                <p class="subtitle"><?= _e( 'If you activate the double opt-in, a confirmation e-mail will be send to the subscribers.', 'egoi-for-wp' ); ?></p>
                <div class="form-group switch-yes-no">
                    <label class="form-switch">
                        <?php $double_optin_enable = $this->bar_post['double_optin'] == 1 || $this->bar_post['list'] == 0 ?>
                        <input id="bar_double_optin" name="egoi_bar_sync[double_optin]" value="1" <?php checked($double_optin_enable, 1); ?> type="checkbox">
                        <i class="form-icon"></i><div class="yes"><?= _e( 'Yes' ); ?></div><div class="no"><?= _e( 'No' ); ?></div>
                    </label>
                </div>
            </div>
            <!-- / Double Opt-In -->
            <!-- LISTAS -->
            <?php get_list_html($this->bar_post['list'], 'egoi_bar_sync[list]') ?>
            <!-- / LISTAS -->
            <!-- lang -->
            <?php get_lang_html($this->bar_post['lang'], 'egoi_bar_sync[lang]', empty($this->bar_post['list'])) ?>
            <!-- / lang -->
            <!-- TAGS -->
            <?php get_tag_html($tag, 'egoi_bar_sync[tag-egoi]'); ?>
            <!-- / TAGS -->
            <!-- BAR TEXT -->
            <div class="smsnf-input-group">
                <label for="text-bar"><?= _e( 'Bar Text', 'egoi-for-wp' ); ?></label>
                <p class="subtitle"><?php _e( 'The text to appear before the email field.', 'egoi-for-wp' ); ?></p>
                <input id="text-bar" type="text" name="egoi_bar_sync[text_bar]" value="<?= $this->bar_post['text_bar']; ?>" autocomplete="off" />
            </div>
            <!-- / BAR TEXT -->
            <!-- BTN TEXT -->
            <div class="smsnf-input-group">
                <label for="text-btn"><?= _e( 'Button Text', 'egoi-for-wp' ); ?></label>
                <p class="subtitle"><?php _e( 'The text on the submit button.', 'egoi-for-wp' ); ?></p>
                <input id="text-btn" type="text" name="egoi_bar_sync[text_button]" value="<?php echo $this->bar_post['text_button']; ?>" autocomplete="off" />
            </div>
            <!-- / BTN TEXT -->
            <!-- PLACEHOLDER EMAIL -->
            <div class="smsnf-input-group">
                <label for="email-placeholde"><?= _e( 'Email Placeholder Text', 'egoi-for-wp' ); ?></label>
                <p class="subtitle"><?php _e( 'The initial placeholder text to appear in the email field.', 'egoi-for-wp' ); ?></p>
                <input id="email-placeholde" type="text" name="egoi_bar_sync[text_email_placeholder]" value="<?php echo $this->bar_post['text_email_placeholder']; ?>" autocomplete="off" />
            </div>
            <!-- / PLACEHOLDER EMAIL -->
        </div>
    </div>

    <div id="smsnf-appearance" class="smsnf-tab-content smsnf-grid">
        <div>
            <!-- BAR POSITION -->
            <div class="smsnf-input-group">
                <label for="bar-position"><?= _e( 'Bar Position', 'egoi-for-wp' ); ?></label>
                <select name="egoi_bar_sync[position]" class="form-select " id="bar-position">
                    <option value="top" <?php selected( $this->bar_post['position'], 'top' ); ?>><?php _e( 'Top', 'egoi-for-wp' ); ?></option>
                    <option value="bottom" <?php selected( $this->bar_post['position'], 'bottom' ); ?>><?php _e( 'Bottom', 'egoi-for-wp' ); ?></option>
                </select>
            </div>
            <!-- / BAR POSITION -->
            <!-- FIXED BAR -->
            <div class="smsnf-input-group">
                <label for="bar_open"><?php _e( 'Bar Fixed?', 'egoi-for-wp' ); ?></label>
                <div class="form-group switch-yes-no">
                    <label class="form-switch">
                        <input id="bar_open" name="egoi_bar_sync[sticky]" value="1" <?php checked($this->bar_post['sticky'], 1); ?> type="checkbox">
                        <i class="form-icon"></i><div class="yes"><?php _e( 'Yes' ); ?></div><div class="no"><?php _e( 'No' ); ?></div>
                    </label>
                </div>
            </div>
            <!-- / FIXED BAR -->
            <!-- BACKGROUND COLOR -->
            <div class="smsnf-input-group">
                <div class="egoi-transparent-option">
                    <label for="bar-background-color"><?php _e( 'Background Color', 'egoi-for-wp' ); ?></label>
                    <span class="e-goi-tooltip">
                        <div class="form-group switch-yes-no">
                        <label class="form-switch">
                            <input id="bar_open" name="egoi_bar_sync[color_bar_transparent]" value="1" <?php checked($this->bar_post['color_bar_transparent'], 1); ?> type="checkbox">
                            <i class="form-icon"></i><div class="yes"><?php _e( 'Yes' ); ?></div><div class="no"><?php _e( 'No' ); ?></div>
                        </label>
                    </div>
 							  	 <span class="e-goi-tooltiptext e-goi-tooltiptext--active">
 							  	 	<?php echo __('Set this to "no" for no background color.','egoi-for-wp'); ?>
 							 	</span>
 							</span>
                </div>

                <div class="colorpicker-wrapper">
                    <div style="background-color:<?= esc_attr( $this->bar_post['color_bar'] ) ?>" class="view" ></div>
                    <input id="bar-background-color" type="text" name="egoi_bar_sync[color_bar]" value="<?= esc_attr( $this->bar_post['color_bar'] ) ?>"  autocomplete="off" />
                    <p><?= _e( 'Select Color', 'egoi-for-wp' ) ?></p>
                </div>
            </div>
            <!-- / BACKGROUND COLOR -->
            <!-- TEXT COLOR -->
            <div class="smsnf-input-group">
                <label for="bar-text-color"><?php _e( 'Text Color', 'egoi-for-wp' ); ?></label>
                <div class="colorpicker-wrapper">
                    <div style="background-color:<?= esc_attr( $this->bar_post['bar_text_color'] ) ?>" class="view" ></div>
                    <input id="bar-text-color" type="text" name="egoi_bar_sync[bar_text_color]" value="<?= esc_attr( $this->bar_post['bar_text_color'] ) ?>"  autocomplete="off" />
                    <p><?= _e( 'Select Color', 'egoi-for-wp' ) ?></p>
                </div>
            </div>
            <!-- / TEXT COLOR -->
            <!-- BORDER SIZE -->
            <div class="smsnf-input-group">
                <label for="bar-border-size"><?php _e( 'Border Size', 'egoi-for-wp' ); ?></label>
                <input  id="bar-border-size" type="text" name="egoi_bar_sync[border_px]" value="<?= esc_attr( $this->bar_post['border_px'] ); ?>" autocomplete="off" />
            </div>
            <!-- / BORDER SIZE -->
            <!-- BORDER COLOR -->
            <div class="smsnf-input-group">
                <label for="bar-text-color"><?php _e( 'Border Color', 'egoi-for-wp' ); ?></label>
                <div class="colorpicker-wrapper">
                    <div style="background-color:<?= esc_attr( $this->bar_post['border_color'] ) ?>" class="view" ></div>
                    <input id="bar-text-color" type="text" name="egoi_bar_sync[border_color]" value="<?= esc_attr( $this->bar_post['border_color'] ) ?>"  autocomplete="off" />
                    <p><?= _e( 'Select Color', 'egoi-for-wp' ) ?></p>
                </div>
            </div>
            <!-- / BORDER COLOR -->
            <!-- BTN COLOR -->
            <div class="smsnf-input-group">
                <label for="bar-text-color"><?php _e( 'Button Color', 'egoi-for-wp' ); ?></label>
                <div class="colorpicker-wrapper">
                    <div style="background-color:<?= esc_attr( $this->bar_post['color_button'] ) ?>" class="view" ></div>
                    <input id="bar-text-color" type="text" name="egoi_bar_sync[color_button]" value="<?= esc_attr( $this->bar_post['color_button'] ) ?>"  autocomplete="off" />
                    <p><?= _e( 'Select Color', 'egoi-for-wp' ) ?></p>
                </div>
            </div>
            <!-- / BTN COLOR -->
            <!-- BTN TEXT COLOR -->
            <div class="smsnf-input-group">
                <label for="bar-text-color"><?php _e( 'Button Text Color', 'egoi-for-wp' ); ?></label>
                <div class="colorpicker-wrapper">
                    <div style="background-color:<?= esc_attr( $this->bar_post['color_button_text'] ) ?>" class="view" ></div>
                    <input id="bar-text-color" type="text" name="egoi_bar_sync[color_button_text]" value="<?= esc_attr( $this->bar_post['color_button_text'] ) ?>"  autocomplete="off" />
                    <p><?= _e( 'Select Color', 'egoi-for-wp' ) ?></p>
                </div>
            </div>
            <!-- / BTN TEXT COLOR -->
            <!-- SUBMIT - SUCCESS -->
            <div class="smsnf-input-group">
                <label for="bar-text-color"><?php _e( 'Background Color on Success', 'egoi-for-wp' ); ?></label>
                <div class="colorpicker-wrapper">
                    <div style="background-color:<?= esc_attr( $this->bar_post['success_bgcolor'] ) ?>" class="view" ></div>
                    <input id="bar-text-color" type="text" name="egoi_bar_sync[success_bgcolor]" value="<?= esc_attr( $this->bar_post['success_bgcolor'] ) ?>"  autocomplete="off" />
                    <p><?= _e( 'Select Color', 'egoi-for-wp' ) ?></p>
                </div>
            </div>
            <!-- / SUBMIT - SUCCESS -->
            <!-- SUBMIT - ERROR -->
            <div class="smsnf-input-group">
                <label for="bar-text-color"><?php _e( 'Background Color on Error', 'egoi-for-wp' ); ?></label>
                <div class="colorpicker-wrapper">
                    <div style="background-color:<?= esc_attr( $this->bar_post['error_bgcolor'] ) ?>" class="view" ></div>
                    <input id="bar-text-color" type="text" name="egoi_bar_sync[error_bgcolor]" value="<?= esc_attr( $this->bar_post['error_bgcolor'] ) ?>"  autocomplete="off" />
                    <p><?= _e( 'Select Color', 'egoi-for-wp' ) ?></p>
                </div>
            </div>
            <!-- / SUBMIT - ERROR -->
        </div>
    </div>

    <div id="smsnf-messages" class="smsnf-tab-content">
        <!-- SUCCESS -->
        <div class="smsnf-input-group">
            <label for="subscribed-msg"><?= _e( 'Success', 'egoi-for-wp' ); ?></label>
            <input id="subscribed-msg" type="text" name="egoi_bar_sync[text_subscribed]" value="<?= esc_attr($this->bar_post['text_subscribed'] ?: __('Success Subscribed', 'egoi-for-wp')); ?>" autocomplete="off" />
        </div>
        <!-- / SUCCESS -->
        <!-- INVALID EMAIL -->
        <div class="smsnf-input-group">
            <label for="invalid-email-msg"><?= _e( 'Invalid email address', 'egoi-for-wp' ); ?></label>
            <input id="invalid-email-msg" type="text" name="egoi_bar_sync[text_invalid_email]" value="<?= esc_attr($this->bar_post['text_invalid_email'] ?: __('Invalid e-mail', 'egoi-for-wp')); ?>" autocomplete="off" />
        </div>
        <!-- / INVALID EMAIL -->
        <!-- Already subscribed -->
        <div class="smsnf-input-group">
            <label for="already-subscribed-msg"><?= _e( 'Already subscribed', 'egoi-for-wp' ); ?></label>
            <input id="already-subscribed-msg" type="text" name="egoi_bar_sync[text_already_subscribed]" value="<?= esc_attr($this->bar_post['text_already_subscribed'] ?: __('Subscriber already exists', 'egoi-for-wp')) ?>" autocomplete="off" />
        </div>
        <!-- / Already subscribed -->
        <!-- Other errors -->
        <div class="smsnf-input-group">
            <label for="bar-other-errors-msg"><?= _e( 'Other errors' ,'egoi-for-wp' ); ?></label>
            <input id="bar-other-errors-msg" type="text" name="egoi_bar_sync[text_error]" placeholder="<?php _e( 'Eg. List Missing from E-goi', 'egoi-for-wp' );?>" value="<?= esc_attr($this->bar_post['text_error']) ?>" autocomplete="off" />
        </div>
        <!-- / Other errors -->
        <!-- Redirect URL -->
        <div class="smsnf-input-group">
            <label for="bar-redirect-url"><?= _e( 'Redirect to URL after successful sign-up', 'egoi-for-wp' ); ?></label>
            <input id="bar-redirect-url" type="text" name="egoi_bar_sync[redirect]" value="<?= esc_url($this->bar_post['redirect']) ?>" placeholder="<?= esc_url( $this->bar_post['redirect'] ); ?>" autocomplete="off" />
            <p class="subtitle"><?php _e( 'Leave empty for no redirect. Otherwise, use complete (absolute) URLs, including <code>http://</code>.', 'egoi-for-wp' ); ?></p>
        </div>
        <!-- / Redirect URL -->
    </div>
    <div class="smsnf-input-group">
        <input type="submit" value="<?= _e('Save', 'egoi-for-wp') ?>">
    </div>
</form>

<style>
    .e-goi-tooltip .e-goi-tooltiptext{
        margin-left: 60px !important;
    }
</style>