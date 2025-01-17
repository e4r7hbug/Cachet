<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CachetHQ\Cachet\Handlers\Commands\Incident;

use CachetHQ\Cachet\Commands\Incident\UpdateIncidentCommand;
use CachetHQ\Cachet\Dates\DateFactory;
use CachetHQ\Cachet\Events\Incident\IncidentWasUpdatedEvent;
use CachetHQ\Cachet\Models\Component;
use CachetHQ\Cachet\Models\Incident;

class UpdateIncidentCommandHandler
{
    /**
     * The date factory instance.
     *
     * @var \CachetHQ\Cachet\Dates\DateFactory
     */
    protected $dates;

    /**
     * Create a new update incident command handler instance.
     *
     * @param \CachetHQ\Cachet\Dates\DateFactory $dates
     *
     * @return void
     */
    public function __construct(DateFactory $dates)
    {
        $this->dates = $dates;
    }

    /**
     * Handle the update incident command.
     *
     * @param \CachetHQ\Cachet\Commands\Incident\UpdateIncidentCommand $command
     *
     * @return \CachetHQ\Cachet\Models\Incident
     */
    public function handle(UpdateIncidentCommand $command)
    {
        $incident = $command->incident;
        $incident->update($this->filterIncidentData($command));

        // The incident occurred at a different time.
        if ($command->incident_date) {
            $incidentDate = $this->dates->createNormalized('d/m/Y H:i', $command->incident_date);

            $incident->update([
                'created_at' => $incidentDate,
                'updated_at' => $incidentDate,
            ]);
        }

        // Update the component.
        if ($command->component_id) {
            Component::find($command->component_id)->update([
                'status' => $command->component_status,
            ]);
        }

        event(new IncidentWasUpdatedEvent($incident));

        return $incident;
    }

    /**
     * Filter the command data.
     *
     * @param \CachetHQ\Cachet\Commands\Incident\UpdateIncidentCommand $command
     *
     * @return array
     */
    protected function filterIncidentData($command)
    {
        return array_filter([
            'name'             => $command->name,
            'status'           => $command->status,
            'message'          => $command->message,
            'visible'          => $command->visible,
            'component_id'     => $command->component_id,
            'component_status' => $command->component_status,
            'notify'           => $command->notify,
        ]);
    }
}
