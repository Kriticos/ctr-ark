<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSectorRequest;
use App\Http\Requests\Admin\UpdateSectorRequest;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SectorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();

        $sectors = Sector::query()
            ->with(['parent', 'children'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.sectors.index', compact('sectors', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $parents = Sector::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('admin.sectors.create', compact('parents', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSectorRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $members = $data['members'] ?? [];
        unset($data['members']);

        /** @var Sector $sector */
        $sector = Sector::create($data);
        $this->syncMembers($sector, $members);

        return redirect()
            ->route('admin.sectors.index')
            ->with('success', 'Setor criado com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Sector $sector): View
    {
        $sector->load(['parent', 'children', 'users']);

        return view('admin.sectors.show', compact('sector'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sector $sector): View
    {
        $sector->load('users');
        $parents = Sector::where('id', '!=', $sector->id)->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        $members = $sector->users->mapWithKeys(
            fn (User $user): array => [$user->id => $user->pivot?->role]
        );

        return view('admin.sectors.edit', compact('sector', 'parents', 'users', 'members'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSectorRequest $request, Sector $sector): RedirectResponse
    {
        $data = $request->validated();
        $members = $data['members'] ?? [];
        unset($data['members']);

        $sector->update($data);
        $this->syncMembers($sector, $members);

        return redirect()
            ->route('admin.sectors.index')
            ->with('success', 'Setor atualizado com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sector $sector): RedirectResponse
    {
        if ($sector->children()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'Não é possível excluir um setor que possui subsetores.');
        }

        if ($sector->procedures()->exists() || $sector->linkedProcedures()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'Não é possível excluir um setor que possui procedimentos.');
        }

        $sector->delete();

        return redirect()
            ->route('admin.sectors.index')
            ->with('success', 'Setor excluído com sucesso.');
    }

    /**
     * @param  array<int, array{user_id:int, role:string}>  $members
     */
    private function syncMembers(Sector $sector, array $members): void
    {
        $syncData = [];

        foreach ($members as $member) {
            if (! empty($member['role'])) {
                $syncData[$member['user_id']] = ['role' => $member['role']];
            }
        }

        $sector->users()->sync($syncData);
    }
}
