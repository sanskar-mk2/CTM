<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ProjectExportController extends Controller
{
    function isLocked($date, $locks)
    {
        $lockEntry = collect($locks)->first(function ($lock) use ($date) {
            return Carbon::parse($lock['date'])->isSameMonth($date);
        });
        return $lockEntry ? $lockEntry['is_locked'] : false;
    }

    function sumActual($activities, $locks)
    {
        return $activities->sum(function ($activity) use ($locks) {
            return $this->isLocked($activity['date'], $locks) ? $activity['value'] : 0;
        });
    }

    function sumTotal($activities)
    {
        return $activities->sum('value');
    }




    public function __invoke(Request $request, Project $project)
    {
        $data = [];
        $actual_or_forecast = [
            'Group' => '',
            'Task' => '',
            'Unit' => '',
            'Start Date' => '',
            'End Date' => '',
            'No. of Units' => '',
            'Unit Price' => '',
            'Total Task Value' => '',
        ];
        foreach ($project->months as $month) {
            $is_locked = $project->locks()->where('date', $month)->first()->is_locked;
            if ($is_locked) {
                $actual_or_forecast[] = 'Actual';
            } else {
                $actual_or_forecast[] = 'Forecast';
            }
        }
        $actual_or_forecast = array_merge($actual_or_forecast, [
            'Units Done' => '',
            'Units incl Forecast' => '',
            'Amount Done' => '',
            'Status' => '',
        ]);
        $data[] = $actual_or_forecast;

        $locks = $project->locks->toArray();

        foreach ($project->groups as $group) {
            foreach ($group->tasks as $task) {
                $task_data = [
                    'Group' => $group->name,
                    'Task' => $task->name,
                    'Unit' => $task->unit,
                    'Start Date' => $task->start_date,
                    'End Date' => $task->end_date,
                    'No. of Units' => $task->quantity,
                    'Unit Price' => $task->price,
                    'Total Task Value' => $task->quantity * $task->price,
                ];
                foreach ($project->months as $month) {
                    $task_data[] = $task->activities()->where('date', $month)->first()->value;
                }
                $task_data = array_merge($task_data, [
                    'Units Done' => $this->sumActual($task->activities, $locks),
                    'Units incl Forecast' => $this->sumTotal($task->activities),
                    'Amount Done' => number_format(
                        $this->sumActual($task->activities, $locks) * $task->price,
                        2,
                        '.',
                        ','
                    ),
                    'Status' =>  $this->sumActual($task->activities, $locks) > $task->quantity
                        ? 'Raise Change Order'
                        : ($this->sumActual($task->activities, $locks) == $task->quantity
                            ? 'Task Done'
                            : 'WIP'),
                ]);
                $data[] = $task_data;
            }
        }
        $convertedDates = array_map(function ($date) {
            return Carbon::parse($date)->format('M-y');
        }, $project->months);

        return Excel::download(new \App\Exports\ProjectExport($data, $convertedDates), 'project_' . $project->project_name . '.csv');
    }
}
