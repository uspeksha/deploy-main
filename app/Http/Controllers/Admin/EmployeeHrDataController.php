<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\EmployeeDocument;
use App\Models\EmployeeFamily;
use App\Models\EmploymentHistory;
use App\Models\PtkpStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class EmployeeHrDataController extends Controller
{
    private function authorizeHrd(User $user): void
    {
        abort_unless(auth()->user()?->role === 'hrd', 403);
        abort_unless($user->role === 'user', 404);
    }

    private function findFamily(User $user, int $familyId): EmployeeFamily
    {
        return $user->families()->findOrFail($familyId);
    }

    private function findHistory(User $user, int $historyId): EmploymentHistory
    {
        return $user->employmentHistories()->findOrFail($historyId);
    }

    private function findDocument(User $user, int $documentId): EmployeeDocument
    {
        return $user->documents()->findOrFail($documentId);
    }

    private function editRedirect(User $user)
    {
        return redirect()->route('admin.employees.edit', $user);
    }

    public function updateProfile(Request $request, User $user)
    {
        $this->authorizeHrd($user);

        $currentPtkpIds = PtkpStatus::query()
            ->effectiveOn(today())
            ->pluck('id')
            ->all();

        $validator = Validator::make($request->all(), [
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'npwp' => ['nullable', 'string', 'max:64'],
            'bpjs_kesehatan_no' => ['nullable', 'string', 'max:64'],
            'bpjs_ketenagakerjaan_no' => ['nullable', 'string', 'max:64'],
            'ptkp_status_id' => ['nullable', 'integer', Rule::in($currentPtkpIds)],
        ]);

        if ($validator->fails()) {
            return $this->editRedirect($user)
                ->withErrors($validator)
                ->withInput($request->except([
                    'npwp',
                    'bpjs_kesehatan_no',
                    'bpjs_ketenagakerjaan_no',
                ]));
        }

        $changes = $validator->validated();
        $profile = $user->profile()->firstOrNew();
        $profile->fill($changes);
        $profile->save();

        ActivityLog::log('HR employee profile updated', 'Employee ID: '.$user->id, [
            'fields' => array_keys($changes),
        ]);

        return $this->editRedirect($user)->with('success', 'Profil HR karyawan berhasil diperbarui.');
    }

    public function storeFamily(Request $request, User $user)
    {
        $this->authorizeHrd($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'relationship' => ['required', 'string', 'max:100'],
            'is_emergency_contact' => ['nullable', 'boolean'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);
        $validated['is_emergency_contact'] = $request->boolean('is_emergency_contact');

        $user->families()->create($validated);

        ActivityLog::log('HR employee family created', 'Employee ID: '.$user->id, [
            'fields' => array_keys($validated),
        ]);

        return $this->editRedirect($user)->with('success', 'Data keluarga berhasil ditambahkan.');
    }

    public function updateFamily(Request $request, User $user, int $family)
    {
        $this->authorizeHrd($user);
        $familyRecord = $this->findFamily($user, $family);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'relationship' => ['required', 'string', 'max:100'],
            'is_emergency_contact' => ['nullable', 'boolean'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);
        $validated['is_emergency_contact'] = $request->boolean('is_emergency_contact');
        $familyRecord->update($validated);

        ActivityLog::log('HR employee family updated', 'Employee ID: '.$user->id, [
            'fields' => array_keys($validated),
        ]);

        return $this->editRedirect($user)->with('success', 'Data keluarga berhasil diperbarui.');
    }

    public function destroyFamily(User $user, int $family)
    {
        $this->authorizeHrd($user);
        $this->findFamily($user, $family)->delete();

        ActivityLog::log('HR employee family deleted', 'Employee ID: '.$user->id, [
            'fields' => ['family_record'],
        ]);

        return $this->editRedirect($user)->with('success', 'Data keluarga berhasil dihapus.');
    }

    public function storeHistory(Request $request, User $user)
    {
        $this->authorizeHrd($user);

        $validated = $request->validate([
            'employment_type' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $user->employmentHistories()->create($validated);

        ActivityLog::log('HR employment history created', 'Employee ID: '.$user->id, [
            'fields' => array_keys($validated),
        ]);

        return $this->editRedirect($user)->with('success', 'Riwayat kerja berhasil ditambahkan.');
    }

    public function updateHistory(Request $request, User $user, int $history)
    {
        $this->authorizeHrd($user);
        $historyRecord = $this->findHistory($user, $history);

        $validated = $request->validate([
            'employment_type' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);
        $historyRecord->update($validated);

        ActivityLog::log('HR employment history updated', 'Employee ID: '.$user->id, [
            'fields' => array_keys($validated),
        ]);

        return $this->editRedirect($user)->with('success', 'Riwayat kerja berhasil diperbarui.');
    }

    public function destroyHistory(User $user, int $history)
    {
        $this->authorizeHrd($user);
        $this->findHistory($user, $history)->delete();

        ActivityLog::log('HR employment history deleted', 'Employee ID: '.$user->id, [
            'fields' => ['employment_history_record'],
        ]);

        return $this->editRedirect($user)->with('success', 'Riwayat kerja berhasil dihapus.');
    }

    public function storeDocument(Request $request, User $user)
    {
        $this->authorizeHrd($user);

        $validated = $request->validate([
            'doc_type' => ['required', 'string', 'max:255'],
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
                'max:5120',
            ],
            'expired_at' => ['nullable', 'date'],
        ]);

        $uploadedFile = $validated['file'];
        $extension = match ($uploadedFile->getMimeType()) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => throw new RuntimeException('Tipe MIME file tidak didukung.'),
        };
        $filePath = $uploadedFile->storeAs(
            'employee-documents/'.$user->id,
            Str::random(48).'.'.$extension,
            'local',
        );

        if (! $filePath) {
            throw new RuntimeException('Dokumen karyawan gagal disimpan ke disk private.');
        }

        try {
            DB::transaction(function () use ($user, $validated, $filePath): void {
                $user->documents()->create([
                    'doc_type' => $validated['doc_type'],
                    'file_path' => $filePath,
                    'expired_at' => $validated['expired_at'] ?? null,
                ]);
            });
        } catch (Throwable $exception) {
            if (Storage::disk('local')->exists($filePath)
                && ! Storage::disk('local')->delete($filePath)) {
                throw new RuntimeException(
                    'Penyimpanan data dokumen gagal dan file sementara tidak dapat dibersihkan.',
                    previous: $exception,
                );
            }

            throw $exception;
        }

        ActivityLog::log('HR employee document uploaded', 'Employee ID: '.$user->id, [
            'fields' => ['doc_type', 'file', 'expired_at'],
        ]);

        return $this->editRedirect($user)->with('success', 'Dokumen karyawan berhasil diunggah.');
    }

    public function downloadDocument(User $user, int $document)
    {
        $this->authorizeHrd($user);
        $documentRecord = $this->findDocument($user, $document);

        abort_unless(Storage::disk('local')->exists($documentRecord->file_path), 404);

        ActivityLog::log('HR employee document viewed', 'Employee ID: '.$user->id, [
            'fields' => ['employee_document'],
        ]);

        return Storage::disk('local')->download($documentRecord->file_path);
    }

    public function destroyDocument(User $user, int $document)
    {
        $this->authorizeHrd($user);
        $documentRecord = $this->findDocument($user, $document);
        $filePath = $documentRecord->file_path;

        DB::transaction(function () use ($documentRecord, $filePath): void {
            if (Storage::disk('local')->exists($filePath)
                && ! Storage::disk('local')->delete($filePath)) {
                throw new RuntimeException('File dokumen tidak dapat dihapus; data dokumen tetap dipertahankan.');
            }

            $documentRecord->delete();
        });

        ActivityLog::log('HR employee document deleted', 'Employee ID: '.$user->id, [
            'fields' => ['employee_document'],
        ]);

        return $this->editRedirect($user)->with('success', 'Dokumen karyawan dan file fisiknya berhasil dihapus.');
    }
}
