<?php

// Trait
require_once dirname(__FILE__) . '/trait/ActionSchedulerHigh.php';
require_once dirname(__FILE__) . '/trait/BaseEnum.php';
require_once dirname(__FILE__) . '/trait/Components.php';
require_once dirname(__FILE__) . '/trait/ConversionDisplayEnum.php';
require_once dirname(__FILE__) . '/trait/EventEnum.php';
require_once dirname(__FILE__) . '/trait/Helpers.php';
require_once dirname(__FILE__) . '/trait/IntegrationTypeEnum.php';
require_once dirname(__FILE__) . '/trait/TriggerEnum.php';
require_once dirname(__FILE__) . '/trait/UserInfo.php';

// Includes
require_once dirname(__FILE__) . '/includes/00-install.php';
require_once dirname(__FILE__) . '/includes/01-head-footer.php';
require_once dirname(__FILE__) . '/includes/02-menu-screen.php';
require_once dirname(__FILE__) . '/includes/03-routes.php';
require_once dirname(__FILE__) . '/includes/04-license.php';
require_once dirname(__FILE__) . '/includes/04-settings.php';
require_once dirname(__FILE__) . '/includes/05-dashboard.php';
require_once dirname(__FILE__) . '/includes/06-actions.php';
require_once dirname(__FILE__) . '/includes/07-crons.php';
require_once dirname(__FILE__) . '/includes/08-page-metabox.php';

/*
 * Resources
 */
// Conversion
require_once dirname(__FILE__) . '/resources/conversion/post-type.php';
require_once dirname(__FILE__) . '/resources/conversion/metabox.php';

// Lead
require_once dirname(__FILE__) . '/resources/lead/post-type.php';
require_once dirname(__FILE__) . '/resources/lead/metabox.php';

// Event
require_once dirname(__FILE__) . '/resources/event/post-type.php';
require_once dirname(__FILE__) . '/resources/event/metabox.php';

// Link Track
require_once dirname(__FILE__) . '/resources/link-track/post-type.php';
require_once dirname(__FILE__) . '/resources/link-track/metabox.php';

// Integration
require_once dirname(__FILE__) . '/resources/integration/00-settings.php';
require_once dirname(__FILE__) . '/resources/integration/01-post-type.php';
require_once dirname(__FILE__) . '/resources/integration/02-metabox.php';
require_once dirname(__FILE__) . '/resources/integration/03-functions.php';
require_once dirname(__FILE__) . '/resources/integration/03-route.php';
require_once dirname(__FILE__) . '/resources/integration/99-model.php';
require_once dirname(__FILE__) . '/resources/integration/blitzpay.php';
require_once dirname(__FILE__) . '/resources/integration/braip.php';
require_once dirname(__FILE__) . '/resources/integration/cakto.php';
require_once dirname(__FILE__) . '/resources/integration/cartpanda.php';
require_once dirname(__FILE__) . '/resources/integration/celetus.php';
require_once dirname(__FILE__) . '/resources/integration/custom.php';
require_once dirname(__FILE__) . '/resources/integration/dmg.php';
require_once dirname(__FILE__) . '/resources/integration/doppus.php';
require_once dirname(__FILE__) . '/resources/integration/eduzz.php';
require_once dirname(__FILE__) . '/resources/integration/eduzz_v2.php';
require_once dirname(__FILE__) . '/resources/integration/green.php';
require_once dirname(__FILE__) . '/resources/integration/hotmart.php';
require_once dirname(__FILE__) . '/resources/integration/hubla.php';
require_once dirname(__FILE__) . '/resources/integration/kirvano.php';
require_once dirname(__FILE__) . '/resources/integration/kiwify.php';
require_once dirname(__FILE__) . '/resources/integration/lastlink.php';
require_once dirname(__FILE__) . '/resources/integration/monetizze.php';
require_once dirname(__FILE__) . '/resources/integration/payt.php';
require_once dirname(__FILE__) . '/resources/integration/perfectpay.php';
require_once dirname(__FILE__) . '/resources/integration/ticto.php';
require_once dirname(__FILE__) . '/resources/integration/voomp.php';
require_once dirname(__FILE__) . '/resources/integration/yampi.php';
require_once dirname(__FILE__) . '/resources/integration/zippify.php';
