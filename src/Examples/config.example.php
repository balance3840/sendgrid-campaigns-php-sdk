<?php

/**
 * SendGrid Campaign Configuration
 * 
 * Copy this file to config.php and fill in your values:
 *   cp config.example.php config.php
 * 
 * Alternatively, set environment variables (they take precedence).
 * 
 * @see https://docs.sendgrid.com/ui/account-and-settings/api-keys
 */

return [
    // =========================================================================
    // API CREDENTIALS
    // =========================================================================
    
    /**
     * SendGrid API Key
     * 
     * Create one at: Settings > API Keys > Create API Key
     * Required permissions: Marketing (Full Access)
     * 
     * @env SENDGRID_API_KEY
     */
    'api_key' => getenv('SENDGRID_API_KEY') ?: 'SG.xxxxxxxxxxxxxxxxxxxxxxxx',

    /**
     * Subuser (optional)
     * 
     * If you're using subusers, specify the subuser's username here
     * to perform actions on their behalf.
     * 
     * @env SENDGRID_SUBUSER
     * @see https://docs.sendgrid.com/ui/account-and-settings/subusers
     */
    'on_behalf_of' => getenv('SENDGRID_SUBUSER') ?: null,

    // =========================================================================
    // SENDER CONFIGURATION
    // =========================================================================

    /**
     * Verified Sender ID
     * 
     * Find this at: Marketing > Senders > click on sender > look at URL
     * The URL will be: https://mc.sendgrid.com/senders/verify?id=XXXXXXX
     * 
     * Or use the API: GET https://api.sendgrid.com/v3/marketing/senders
     * 
     * @env SENDGRID_SENDER_ID
     * @see https://docs.sendgrid.com/ui/sending-email/sender-verification
     */
    'sender_id' => (int) (getenv('SENDGRID_SENDER_ID') ?: 0),

    /**
     * Unsubscribe Group ID (Suppression Group)
     * 
     * Find this at: Marketing > Unsubscribe Groups > click group > ID in URL
     * 
     * Or use the API: GET https://api.sendgrid.com/v3/asm/groups
     * 
     * @env SENDGRID_SUPPRESSION_GROUP_ID
     * @see https://docs.sendgrid.com/ui/sending-email/unsubscribe-groups
     */
    'suppression_group_id' => (int) (getenv('SENDGRID_SUPPRESSION_GROUP_ID') ?: 0),

    // =========================================================================
    // CAMPAIGN DETAILS
    // =========================================================================

    'campaign' => [
        /**
         * Contact list name
         * Timestamp is appended to avoid conflicts on repeated runs
         */
        'list_name' => 'My Campaign List - ' . date('Y-m-d H:i'),

        /**
         * Email design/template name
         */
        'design_name' => 'My Campaign Design - ' . date('Y-m-d H:i'),

        /**
         * Single Send campaign name
         */
        'single_send_name' => 'My Campaign - ' . date('Y-m-d H:i'),

        /**
         * Email subject line
         * Supports substitution tags: {{first_name}}, {{last_name}}, etc.
         */
        'subject' => 'Hello {{first_name}}!',
    ],

    // =========================================================================
    // CONTACTS
    // =========================================================================

    /**
     * Contacts to import
     * 
     * Available fields:
     *   - email (required)
     *   - first_name
     *   - last_name
     *   - address_line_1, address_line_2
     *   - city, state_province_region, postal_code, country
     *   - phone_number
     *   - custom_fields (array of field_name => value)
     */
    'contacts' => [
        [
            'email' => 'recipient1@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'custom_fields' => [
                'corporation_id' => 100,
                // Add more custom fields as needed
            ],
        ],
        [
            'email' => 'recipient2@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'custom_fields' => [
                'corporation_id' => 100,
            ],
        ],
        // Add more contacts as needed...
    ],

    // =========================================================================
    // TIMING
    // =========================================================================

    /**
     * Seconds to wait for contacts to sync
     * 
     * SendGrid requires time to process contacts before they can be emailed.
     * Minimum recommended: 120 seconds for small lists, 300+ for larger lists.
     */
    'contact_sync_wait_seconds' => 180,

    /**
     * Seconds to wait after creating Single Send before scheduling
     * 
     * Brief delay to ensure the Single Send is fully created.
     */
    'single_send_wait_seconds' => 30,

    // =========================================================================
    // EXECUTION MODE
    // =========================================================================

    /**
     * Whether to actually send the campaign
     * 
     * false = Dry run (creates all resources but doesn't send)
     * true  = Live run (sends the campaign immediately)
     * 
     * ⚠️  WARNING: Set to true only when you're ready to send real emails!
     */
    'actually_send' => false,
];