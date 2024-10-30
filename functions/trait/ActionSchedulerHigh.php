<?php

function PXA_cpu_calc()
{
    $cpus = sys_getloadavg()[2];

    return ceil($cpus / 4);
}

add_filter('action_scheduler_queue_runner_time_limit', function ($time_limit) {
    return 120;
});

add_filter('action_scheduler_queue_runner_batch_size', function ($batch_size) {
    // return PXA_cpu_calc() * 20;
    return 25;
});

// add_filter('action_scheduler_queue_runner_concurrent_batches', function ($concurrent_batches) {
//     $workers = PXA_cpu_calc();
//
//     return ($workers > 1) ? $workers : 1;
//     // return 5;
// });

add_action('wp_ajax_nopriv_eg_create_additional_runners', function () {
    if (isset($_POST['ashp_nonce']) && isset($_POST['instance']) && wp_verify_nonce($_POST['ashp_nonce'], 'ashp_additional_runner_' . $_POST['instance'])) {
        ActionScheduler_QueueRunner::instance()->run();
    }

    wp_die();
}, 0);

add_filter('action_scheduler_default_cleaner_statuses', function ($statuses) {
    $statuses[] = ActionScheduler_Store::STATUS_FAILED;

    return $statuses;
});
