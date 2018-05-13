<?php

namespace Drupal\amp\Form;

use Drupal\amp\EntityTypeInfo;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the configuration export form.
 */
class AmpSettingsForm extends ConfigFormBase {

  /**
   * The theme handler service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The array of valid theme options.
   *
   * @array $themeOptions
   */
  private $themeOptions;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $tagInvalidate;

  /**
   * Information about AMP-enabled content types.
   *
   * @var \Drupal\amp\EntityTypeInfo
   */
  protected $entityTypeInfo;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'amp_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['amp.settings', 'amp.theme'];
  }

  /*
   * Helper function to get available theme options.
   *
   * @return array
   *   Array of valid themes.
   */
  private function getThemeOptions() {
    // Get all available themes.
    $themes = $this->themeHandler->rebuildThemeData();
    uasort($themes, 'system_sort_modules_by_info_name');
    $theme_options = [];

    foreach ($themes as $theme) {
      if (!empty($theme->info['hidden'])) {
        continue;
      }
      elseif (!empty($theme->status)) {
        $theme_options[$theme->getName()] = $theme->info['name'];
      }
    }

    return $theme_options;
  }

  /**
   * Constructs a AmpSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $tag_invalidate
   *   The cache tags invalidator.
   * @param \Drupal\amp\EntityTypeInfo $entity_type_info
   *   Information about AMP-enabled content types.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ThemeHandlerInterface $theme_handler, CacheTagsInvalidatorInterface $tag_invalidate, EntityTypeInfo $entity_type_info) {
    parent::__construct($config_factory);

    $this->themeHandler = $theme_handler;
    $this->themeOptions = $this->getThemeOptions();
    $this->tagInvalidate = $tag_invalidate;
    $this->entityTypeInfo = $entity_type_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('theme_handler'),
      $container->get('cache_tags.invalidator'),
      $container->get('amp.entity_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $amp_config = $this->config('amp.settings');

    if (\Drupal::moduleHandler()->moduleExists('field_ui')) {
      $form['amp_content_amp_status'] = [
        '#title' => $this->t('AMP Status by Content Type'),
        '#theme' => 'item_list',
        '#items' => $this->entityTypeInfo->getFormattedAmpEnabledTypes(),
      ];
    }
    else {
      $form['amp_content_amp_status'] = [
        '#type' => 'item',
        '#title' => $this->t('AMP Status by Content Type'),
        '#markup' => $this->t('(In order to enable and disable AMP content types in the UI, the Field UI module must be enabled.)'),
      ];
    }

    $amptheme_config = $this->config('amp.theme');
    $form['amptheme'] = [
      '#type' => 'select',
      '#options' => $this->themeOptions,
      '#required' => TRUE,
      '#title' => $this->t('AMP theme'),
      '#description' => $this->t('Choose a theme to use for AMP pages. Make sure the theme is installed before trying to use it.'),
      '#default_value' => $amptheme_config->get('amptheme'),
    ];
    if (empty($this->themeOptions)) {
      $form['amptheme']['#description'] = $this->t('To use the AMP module, an AMP theme must be installed. You can choose between AMP Base or an installed subtheme of AMP Base, such as the ExAMPle Subtheme, for use as the AMP Theme, or create your own AMP theme as a subtheme of your primary theme.');
    }

   $form['google_analytics_id'] = [
      '#type' => 'textfield',
      '#default_value' => $amp_config->get('google_analytics_id'),
      '#title' => $this->t('Google Analytics Web Property ID'),
      '#description' => $this->t('This ID is unique to each site you want to track separately, and is in the form of UA-xxxxxxx-yy. To get a Web Property ID, <a href=":analytics">register your site with Google Analytics</a>, or if you already have registered your site, go to your Google Analytics Settings page to see the ID next to every site profile. <a href=":webpropertyid">Find more information in the documentation</a>.', [':analytics' => 'http://www.google.com/analytics/', ':webpropertyid' => Url::fromUri('https://developers.google.com/analytics/resources/concepts/gaConceptsAccounts', ['fragment' => 'webProperty'])->toString()]),
      '#maxlength' => 20,
      '#size' => 15,
      '#placeholder' => 'UA-',
    ];

    $form['google_adsense_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Google AdSense Publisher ID'),
      '#default_value' => $amp_config->get('google_adsense_id'),
      '#maxlength' => 25,
      '#size' => 20,
      '#placeholder' => 'pub-',
      '#description' => $this->t('This is the Google AdSense Publisher ID for the site owner. Get this in your Google Adsense account. It should be similar to pub-9999999999999'),
    );

    $form['google_doubleclick_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Google DoubleClick for Publishers Network ID'),
      '#default_value' => $amp_config->get('google_doubleclick_id'),
      '#maxlength' => 25,
      '#size' => 20,
      '#placeholder' => '/',
      '#description' => $this->t('The Network ID to use on all tags. This value should begin with a /.'),
    );

    $form['pixel_group'] = array(
      '#type' => 'fieldset',
      '#title' => t('amp-pixel'),
    );
    $form['pixel_group']['amp_pixel'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable amp-pixel'),
      '#default_value' => $amp_config->get('amp_pixel'),
      '#description' => $this->t('The amp-pixel element is meant to be used as a typical tracking pixel -- to count page views. Find more information in the <a href="https://www.ampproject.org/docs/reference/amp-pixel.html">amp-pixel documentation</a>.'),
    );
    $form['pixel_group']['amp_pixel_domain_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('amp-pixel domain name'),
      '#default_value' => $amp_config->get('amp_pixel_domain_name'),
      '#description' => $this->t('The domain name where the tracking pixel will be loaded: do not include http or https.'),
      '#states' => array('visible' => array(
        ':input[name="amp_pixel"]' => array('checked' => TRUE))
      ),
    );
    $form['pixel_group']['amp_pixel_query_string'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('amp-pixel query path'),
      '#default_value' => $amp_config->get('amp_pixel_query_string'),
      '#description' => $this->t('The path at the domain where the GET request will be received, e.g. "pixel" in example.com/pixel?RANDOM.'),
      '#states' => array('visible' => array(
        ':input[name="amp_pixel"]' => array('checked' => TRUE))
      ),
    );
    $form['pixel_group']['amp_pixel_random_number'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Random number'),
      '#default_value' => $amp_config->get('amp_pixel_random_number'),
      '#description' => $this->t('Use the special string RANDOM to add a random number to the URL if required. Find more information in the <a href="https://github.com/ampproject/amphtml/blob/master/spec/amp-var-substitutions.md#random">amp-pixel documentation</a>.'),
      '#states' => array('visible' => array(
        ':input[name="amp_pixel"]' => array('checked' => TRUE))
      ),
    );

    $form['amp_library_group'] = array(
      '#type' => 'fieldset',
      '#title' => t('AMP Library Configuration <a href="https://github.com/Lullabot/amp-library">(GitHub Home and Documentation)</a>'),
    );

    $form['amp_library_group']['test_page'] = array(
      '#type' => 'item',
      '#markup' => t('<a href=":url">Test that AMP is configured properly</a>', array(':url' => Url::fromRoute('amp.test_library_hello')->toString())),
      '#suffix' => $this->t('If you want to see AMP formatter specific warning for one node add query ' .
          '"development=1" at end of a node url. e.g. <strong>node/12345?amp&development=1</strong>'),
    );

    $form['experimental'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Experimental features"),
    ];

    $form['experimental']['amp_everywhere'] = [
      '#type' => 'checkbox',
      '#default_value' => $amp_config->get('amp_everywhere'),
      '#title' => $this->t('Generate all pages as AMP pages?'),
      '#description' => $this->t('Set this to FALSE if you want AMP pages displayed as an alternative to your normal pages, on a different path (two pages for each item, the traditional way of deploying AMP). Choose TRUE if you want your normal pages to also be the AMP pages (there is only one page for each item, which is both the canonical page and the AMP page). If you are not sure what what this means, leave it set to FALSE.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Validate the Google Analytics ID.
    if (!empty($form_state->getValue('google_analytics_id'))) {
      $form_state->setValue('google_analytics_id', trim($form_state->getValue('google_analytics_id')));
      // Replace all type of dashes (n-dash, m-dash, minus) with normal dashes.
      $form_state->setValue('google_analytics_id', str_replace(['–', '—', '−'], '-', $form_state->getValue('google_analytics_id')));
      if (!preg_match('/^UA-\d+-\d+$/', $form_state->getValue('google_analytics_id'))) {
        $form_state->setErrorByName('google_analytics_id', t('A valid Google Analytics Web Property ID is case sensitive and formatted like UA-xxxxxxx-yy.'));
      }
    }

    // Validate the Google Adsense ID.
    if (!empty($form_state->getValue('google_adsense_id'))) {
      $form_state->setValue('google_adsense_id', trim($form_state->getValue('google_adsense_id')));
      if (!preg_match('/^pub-[0-9]+$/', $form_state->getValue('google_adsense_id'))) {
        $form_state->setErrorByName('google_adsense_id', t('A valid Google AdSense Publisher ID is case sensitive and formatted like pub-9999999999999'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // AMP theme settings.
    $amptheme = $form_state->getValue('amptheme');
    $amptheme_config = $this->config('amp.theme');
    $amptheme_config->setData(['amptheme' => $amptheme]);
    $amptheme_config->save();

    // Other module settings.
    $amp_config = $this->config('amp.settings');

    $amp_config->set('google_analytics_id', $form_state->getValue('google_analytics_id'))->save();
    $amp_config->set('google_adsense_id', $form_state->getValue('google_adsense_id'))->save();
    $amp_config->set('google_doubleclick_id', $form_state->getValue('google_doubleclick_id'))->save();

    $amp_config->set('amp_pixel', $form_state->getValue('amp_pixel'))->save();
    $amp_config->set('amp_pixel_domain_name', $form_state->getValue('amp_pixel_domain_name'))->save();
    $amp_config->set('amp_pixel_query_string', $form_state->getValue('amp_pixel_query_string'))->save();
    $amp_config->set('amp_pixel_random_number', $form_state->getValue('amp_pixel_random_number'))->save();

    $amp_config->set('amp_everywhere', $form_state->getValue('amp_everywhere'))->save();

    parent::submitForm($form, $form_state);
  }
}
