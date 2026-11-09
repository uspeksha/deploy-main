<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\JobLevel;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HrMasterDataController extends Controller
{
    /**
     * @return array<string, array<string, mixed>>
     */
    private function resources(): array
    {
        return [
            'departments' => [
                'model' => Department::class,
                'label' => 'Departemen',
                'description' => 'Kelola departemen dan struktur induknya.',
                'columns' => ['name' => 'Nama', 'parent.name' => 'Departemen induk'],
                'with' => ['parent'],
                'fields' => [
                    'parent_id' => ['label' => 'Departemen induk (opsional)', 'type' => 'select', 'source' => 'departments', 'rules' => ['nullable', 'integer', 'exists:departments,id']],
                    'name' => ['label' => 'Nama departemen', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                ],
            ],
            'job-levels' => [
                'model' => JobLevel::class,
                'label' => 'Level Jabatan',
                'description' => 'Kelola tingkat atau urutan level jabatan.',
                'columns' => ['name' => 'Nama', 'rank_order' => 'Urutan'],
                'with' => [],
                'fields' => [
                    'name' => ['label' => 'Nama level', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    'rank_order' => ['label' => 'Urutan', 'type' => 'number', 'step' => '1', 'rules' => ['required', 'integer', 'min:0']],
                ],
            ],
            'positions' => [
                'model' => Position::class,
                'label' => 'Jabatan',
                'description' => 'Kelola jabatan beserta departemen dan levelnya.',
                'columns' => ['name' => 'Nama', 'department.name' => 'Departemen', 'jobLevel.name' => 'Level'],
                'with' => ['department', 'jobLevel'],
                'fields' => [
                    'department_id' => ['label' => 'Departemen', 'type' => 'select', 'source' => 'departments', 'rules' => ['required', 'integer', 'exists:departments,id']],
                    'job_level_id' => ['label' => 'Level jabatan', 'type' => 'select', 'source' => 'job_levels', 'rules' => ['required', 'integer', 'exists:job_levels,id']],
                    'name' => ['label' => 'Nama jabatan', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                ],
            ],
            'shifts' => [
                'model' => Shift::class,
                'label' => 'Shift',
                'description' => 'Kelola data master shift.',
                'warning' => 'Jam shift dan toleransi adalah placeholder kebijakan; shift belum dipakai untuk menghitung keterlambatan. Patokan telat lama tidak berubah.',
                'columns' => ['name' => 'Nama', 'start_time' => 'Mulai', 'end_time' => 'Selesai', 'late_tolerance_min' => 'Toleransi (menit)', 'is_overnight' => 'Lintas malam'],
                'with' => [],
                'fields' => [
                    'name' => ['label' => 'Nama shift', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    'start_time' => ['label' => 'Jam mulai', 'type' => 'time', 'rules' => ['required', 'date_format:H:i']],
                    'end_time' => ['label' => 'Jam selesai', 'type' => 'time', 'rules' => ['required', 'date_format:H:i']],
                    'late_tolerance_min' => ['label' => 'Toleransi telat (menit)', 'type' => 'number', 'step' => '1', 'rules' => ['required', 'integer', 'min:0']],
                    'is_overnight' => ['label' => 'Shift melewati tengah malam', 'type' => 'checkbox', 'rules' => ['required', 'boolean']],
                ],
            ],
            'leave-types' => [
                'model' => LeaveType::class,
                'label' => 'Jenis Cuti',
                'description' => 'Kelola master jenis cuti.',
                'warning' => 'Kuota dan status berbayar adalah placeholder kebijakan perusahaan; nilai ini belum digunakan untuk menghitung atau memotong saldo cuti.',
                'columns' => ['name' => 'Nama', 'default_quota' => 'Kuota default (contoh)', 'is_paid' => 'Berbayar', 'need_attachment' => 'Perlu lampiran'],
                'with' => [],
                'fields' => [
                    'name' => ['label' => 'Nama jenis cuti', 'type' => 'text', 'rules' => ['required', 'string', 'max:255']],
                    'default_quota' => ['label' => 'Kuota default (placeholder)', 'type' => 'number', 'step' => '1', 'rules' => ['required', 'integer', 'min:0']],
                    'is_paid' => ['label' => 'Cuti berbayar (placeholder)', 'type' => 'checkbox', 'rules' => ['required', 'boolean']],
                    'need_attachment' => ['label' => 'Memerlukan lampiran', 'type' => 'checkbox', 'rules' => ['required', 'boolean']],
                ],
            ],
        ];
    }

    private function definition(string $resource): array
    {
        abort_unless(isset($this->resources()[$resource]), 404);

        return $this->resources()[$resource];
    }

    private function ensureHrd(): void
    {
        abort_unless(auth()->user()?->role === 'hrd', 403);
    }

    public function index(Request $request, string $resource): View
    {
        $this->ensureHrd();
        $definition = $this->definition($resource);
        $model = $definition['model'];
        $records = $model::query()
            ->with($definition['with'])
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.master-data.index', compact('resource', 'definition', 'records'));
    }

    public function create(string $resource): View
    {
        $this->ensureHrd();
        $definition = $this->definition($resource);
        $item = null;
        $options = $this->options($resource);

        return view('admin.master-data.form', compact('resource', 'definition', 'item', 'options'));
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $this->ensureHrd();
        $definition = $this->definition($resource);
        $validated = $this->validatedData($request, $definition);
        $this->validateDepartmentHierarchy($resource, $validated);

        $model = new $definition['model'];
        $model->fill($validated);
        $model->save();
        $this->logWrite('created', $definition, $model);

        return redirect()
            ->route('admin.master-data.index', ['resource' => $resource])
            ->with('success', "{$definition['label']} berhasil ditambahkan.");
    }

    public function edit(string $resource, int $record): View
    {
        $this->ensureHrd();
        $definition = $this->definition($resource);
        $model = $definition['model'];
        $item = $model::query()->findOrFail($record);
        $options = $this->options($resource, $item);

        return view('admin.master-data.form', compact('resource', 'definition', 'item', 'options'));
    }

    public function update(Request $request, string $resource, int $record): RedirectResponse
    {
        $this->ensureHrd();
        $definition = $this->definition($resource);
        $modelClass = $definition['model'];
        $item = $modelClass::query()->findOrFail($record);
        $validated = $this->validatedData($request, $definition);
        $this->validateDepartmentHierarchy($resource, $validated, $item instanceof Department ? $item : null);

        $item->fill($validated);
        $item->save();
        $this->logWrite('updated', $definition, $item);

        return redirect()
            ->route('admin.master-data.index', ['resource' => $resource])
            ->with('success', "{$definition['label']} berhasil diperbarui.");
    }

    public function destroy(string $resource, int $record): RedirectResponse
    {
        $this->ensureHrd();
        $definition = $this->definition($resource);
        $modelClass = $definition['model'];
        $item = $modelClass::query()->findOrFail($record);
        $blockReason = $this->deleteBlockReason($resource, $item);

        if ($blockReason !== null) {
            return redirect()
                ->route('admin.master-data.index', ['resource' => $resource])
                ->with('error', "Tidak dapat menghapus {$definition['label']}: {$blockReason}");
        }

        $this->logWrite('deleted', $definition, $item);
        $item->delete();

        return redirect()
            ->route('admin.master-data.index', ['resource' => $resource])
            ->with('success', "{$definition['label']} berhasil dihapus.");
    }

    private function validatedData(Request $request, array $definition): array
    {
        foreach ($definition['fields'] as $name => $field) {
            if ($field['type'] === 'checkbox') {
                $request->merge([$name => $request->boolean($name)]);
            }
        }

        return $request->validate(array_map(
            fn (array $field): array => $field['rules'],
            $definition['fields'],
        ));
    }

    private function validateDepartmentHierarchy(string $resource, array $data, ?Department $current = null): void
    {
        if ($resource !== 'departments') {
            return;
        }

        if (empty($data['parent_id'])) {
            return;
        }

        $parent = Department::query()->findOrFail($data['parent_id']);
        $visited = [];
        $ancestor = $parent;
        while ($ancestor !== null) {
            if (($current && $ancestor->is($current)) || isset($visited[$ancestor->id])) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Pilihan departemen induk akan membuat siklus.',
                ]);
            }

            $visited[$ancestor->id] = true;
            $ancestor = $ancestor->parent;
        }
    }

    /**
     * @return array<string, Collection<int, Model>>
     */
    private function options(string $resource, ?Model $item = null): array
    {
        $sources = collect($this->definition($resource)['fields'])
            ->pluck('source')
            ->filter()
            ->unique();
        $options = [];

        foreach ($sources as $source) {
            $options[$source] = match ($source) {
                'departments' => Department::query()
                    ->when($item instanceof Department, fn ($query) => $query->whereKeyNot($item->getKey()))
                    ->orderBy('name')
                    ->get(),
                'job_levels' => JobLevel::query()->orderBy('rank_order')->orderBy('name')->get(),
                default => collect(),
            };
        }

        return $options;
    }

    private function deleteBlockReason(string $resource, Model $item): ?string
    {
        if ($resource === 'departments' && $item instanceof Department) {
            return $this->firstUsedReason([
                [$item->children(), 'masih menjadi induk departemen lain'],
                [$item->positions(), 'masih memiliki jabatan'],
                [$item->users(), 'masih digunakan oleh karyawan'],
            ]);
        }

        if ($resource === 'job-levels' && $item instanceof JobLevel) {
            return $this->firstUsedReason([
                [$item->positions(), 'masih digunakan oleh jabatan'],
            ]);
        }

        if ($resource === 'positions' && $item instanceof Position) {
            return $this->firstUsedReason([
                [$item->users(), 'masih digunakan oleh karyawan'],
            ]);
        }

        if ($resource === 'shifts' && $item instanceof Shift) {
            return $this->firstUsedReason([
                [$item->schedules(), 'masih digunakan oleh jadwal karyawan'],
                [$item->absensis(), 'masih tercatat pada absensi'],
            ]);
        }

        if ($resource === 'leave-types' && $item instanceof LeaveType) {
            return $this->firstUsedReason([
                [$item->balances(), 'masih memiliki saldo cuti'],
                [$item->absensis(), 'masih digunakan oleh pengajuan absensi'],
            ]);
        }

        return 'jenis data tidak dikenal';
    }

    /**
     * @param  array<int, array{0: mixed, 1: string}>  $relations
     */
    private function firstUsedReason(array $relations): ?string
    {
        foreach ($relations as [$relation, $reason]) {
            if ($relation->exists()) {
                return $reason;
            }
        }

        return null;
    }

    private function logWrite(string $operation, array $definition, Model $item): void
    {
        $recordLabel = $item->getAttribute('name') ?? $item->getKey();
        ActivityLog::log(
            "HR master data {$operation}",
            "{$definition['label']} #{$item->getKey()}",
            "Operasi {$operation} pada {$definition['label']}: {$recordLabel}",
        );
    }
}
