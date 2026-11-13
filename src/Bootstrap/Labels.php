<?php

/**
 * -------------------------------------------------------------------------
 * TicketBoost plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of TicketBoost.
 *
 * TicketBoost is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * TicketBoost is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with TicketBoost. If not, see <https://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2026 by Juan A. Escobar.
 * @license   GPLv3+ https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/scobjuan/ticketboost
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Ticketboost\Bootstrap;

/**
 * Every text the frontend shows, translated with gettext. The bundle ships no user-facing
 * text; it looks labels up by key in the bootstrap payload.
 */
final class Labels
{
    /**
     * @return array<string, string> Label key to translated text, for the current language.
     */
    public static function all(): array
    {
        return [
            'ui.title'            => __('TicketBoost', 'ticketboost'),
            'ui.all_shortcuts'    => __('All shortcuts', 'ticketboost'),
            'ui.search'           => __('Search...', 'ticketboost'),
            'ui.no_results'       => __('No results', 'ticketboost'),
            'ui.search_failed'    => __('The search failed', 'ticketboost'),
            'ui.hint.help'        => __('all shortcuts', 'ticketboost'),
            'ui.hint.close'       => __('close', 'ticketboost'),
            'ui.hint.back'        => __('back', 'ticketboost'),
            'ui.hint.select'      => __('select', 'ticketboost'),
            'ui.hint.toggle'      => __('mark', 'ticketboost'),
            'ui.hint.confirm'     => __('confirm', 'ticketboost'),
            'group.navigate'      => __('Navigate', 'ticketboost'),
            'group.assign'        => __('Assign', 'ticketboost'),
            'group.ticket'        => __('Ticket', 'ticketboost'),
            'group.general'       => __('General', 'ticketboost'),
            'action.next'         => __('Next ticket', 'ticketboost'),
            'action.previous'     => __('Previous ticket', 'ticketboost'),
            'action.list'         => __('Back to the list', 'ticketboost'),
            'action.my_tickets'   => __('My tickets', 'ticketboost'),
            'action.assign_me'    => __('Assign to me', 'ticketboost'),
            'action.assign_other' => __('Assign to someone else', 'ticketboost'),
            'action.unassign_me'  => __('Unassign me', 'ticketboost'),
            'action.unassign_others' => __('Unassign others', 'ticketboost'),
            'action.change_status'   => __('Change status', 'ticketboost'),
            'action.close'           => __('Close ticket', 'ticketboost'),
            'action.followup'        => __('Add a followup', 'ticketboost'),
            'action.task'            => __('Add a task', 'ticketboost'),
            'action.change_priority' => __('Change priority', 'ticketboost'),
            'action.help'            => __('Show all shortcuts', 'ticketboost'),
            'message.action_failed'  => __('The action failed', 'ticketboost'),
        ];
    }
}
