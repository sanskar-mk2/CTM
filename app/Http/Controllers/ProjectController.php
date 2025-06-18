<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Team;
use App\Models\User;
use Inertia\Inertia;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects based on user role and permissions.
     *
     * This function retrieves and returns a list of projects based on the authenticated user's role:
     * - Super Admin and Admin roles can see all projects.
     * - Manager and Executive roles can see projects they are assigned to directly or through their team.
     * - Other roles (e.g., regular users) will see an empty list.
     *
     * The function uses the Spatie Roles and Permissions package for role checking.
     * It also utilizes Laravel's Eloquent polymorphic relationships for efficient querying.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole(['Super Admin', 'Admin'])) {
            // Super Admin and Admin can see all projects
            $projects = Project::with('createdBy', 'groups.tasks.activities', 'locks')->get();
        } elseif ($user->hasRole(['Manager', 'Executive'])) {
            // Manager and Executive can see projects they are assigned to directly or through their team
            $projects = Project::with('createdBy', 'groups.tasks.activities', 'locks')
                ->where(function ($query) use ($user) {
                    $query->whereHas('users', function ($q) use ($user) {
                        $q->where('project_assignments.assignable_id', $user->id)
                            ->where('project_assignments.assignable_type', User::class);
                    })
                        ->orWhereHas('teams', function ($q) use ($user) {
                            $q->whereHas('users', function ($u) use ($user) {
                                $u->where('users.id', $user->id);
                            });
                        });
                })
                ->get();
        } else {
            // Other roles see an empty list
            $projects = collect();
        }

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Projects/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request)
    {
        $project = Project::make($request->validated());
        $project->created_by = auth()->id();
        $project->save();

        return redirect()->route('projects.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        return Inertia::render('Projects/Show', [
            'project' => $project->load('groups.tasks.activities', 'locks', 'users', 'teams'),
            'users' => User::all(),
            'teams' => Team::all(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('projects.index');
    }
}
