<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Notifications\Workflow\Step\Event\Filter;

class Post_Status extends Base implements Filter_Interface
{

    const META_KEY_POST_STATUS_FROM = '_psppno_poststatfrom';

    const META_KEY_POST_STATUS_TO = '_psppno_poststatto';

    /**
     * Function to render and returnt the HTML markup for the
     * Field in the form.
     *
     * @return string
     */
    public function render()
    {
        echo $this->get_service('twig')->render(
            'workflow_filter_post_status.twig',
            [
                'name'         => "publishpress_notif[{$this->step_name}_filters][post_status]",
                'id'           => "publishpress_notif_{$this->step_name}_filters_post_status",
                'options_from' => $this->get_options('from'),
                'options_to'   => $this->get_options('to'),
                'labels'       => [
                    'status_transition' => esc_html__('When the status is changed', 'publishpress'),
                    'from'              => esc_html__('Previous status', 'publishpress'),
                    'to'                => esc_html__('New status', 'publishpress'),
                    'any'               => esc_html__('- any status -', 'publishpress'),
                ],
            ]
        );
    }

    /**
     * Returns a list of post types in the options format
     *
     * @return array
     */
    protected function get_options($group)
    {
        $statuses = $this->get_post_statuses();
        $metadata = (array)$this->get_metadata('');
        $options  = [];

        if ('from' === $group) {
            // Add an status to represent new posts
            $options = [
                [
                    'value'    => 'auto-draft',
                    'label'    => __('"New"', 'publishpress'),
                    'selected' => in_array('auto-draft', $metadata[$group]),
                ],
            ];
        }

        foreach ($statuses as $status)
        {
            $options[] = [
                'value'    => $status->slug,
                'label'    => $status->name,
                'selected' => in_array($status->slug, $metadata[$group]),
            ];
        }

        return $options;
    }

    /**
     * Returns the metadata of the current post.
     *
     * @param string $meta_key
     * @param bool   $single
     * @return mixed
     */
    public function get_metadata($meta_key, $single = false)
    {
        return [
            'from' => parent::get_metadata(static::META_KEY_POST_STATUS_FROM),
            'to'   => parent::get_metadata(static::META_KEY_POST_STATUS_TO),
        ];
    }

    /**
     * Function to save the metadata from the metabox
     *
     * @param int     $id
     * @param WP_Post $post
     */
    public function save_metabox_data($id, $post)
    {
        // From
        if (!isset($_POST['publishpress_notif']["{$this->step_name}_filters"]['post_status']['from']))
        {
            $from = [];
        } else
        {
            $from = $_POST['publishpress_notif']["{$this->step_name}_filters"]['post_status']['from'];
        }

        $this->update_metadata_array($id, static::META_KEY_POST_STATUS_FROM, $from);

        // To
        if (!isset($_POST['publishpress_notif']["{$this->step_name}_filters"]['post_status']['to']))
        {
            $to = [];
        } else
        {
            $to = $_POST['publishpress_notif']["{$this->step_name}_filters"]['post_status']['to'];
        }
        $this->update_metadata_array($id, static::META_KEY_POST_STATUS_TO, $to);
    }

    /**
     * Filters and returns the arguments for the query which locates
     * workflows that should be executed.
     *
     * @param array $query_args
     * @param array $action_args
     * @return array
     */
    public function get_run_workflow_query_args($query_args, $action_args)
    {

        // From
        $query_args['meta_query'][] = [
            [
                'key'     => static::META_KEY_POST_STATUS_FROM,
                'value'   => $action_args['old_status'],
                'type'    => 'CHAR',
                'compare' => '=',
            ],
        ];

        // To
        $query_args['meta_query'][] = [
            [
                'key'     => static::META_KEY_POST_STATUS_TO,
                'value'   => $action_args['new_status'],
                'type'    => 'CHAR',
                'compare' => '=',
            ],
        ];

        return parent::get_run_workflow_query_args($query_args, $action_args);
    }
}
