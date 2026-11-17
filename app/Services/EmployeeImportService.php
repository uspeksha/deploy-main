<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class EmployeeImportService
{
    private const MAX_ROWS = 500;

    private const HEADER_ALIASES = [
        'id_karyawan' => ['employee id', 'id karyawan'],
        'name' => ['full name', 'name', 'nama lengkap'],
        'email' => ['email'],
        'phone' => ['mobile phone', 'phone', 'nomor telepon'],
        'address' => ['residential address', 'address', 'alamat'],
        'departemen' => ['department', 'departemen', 'organization'],
        'employment_type' => ['employment type', 'jenis karyawan'],
        'start_date' => ['join date', 'start date', 'tanggal mulai kerja'],
        'work_location' => ['work location', 'lokasi kerja'],
        'nik' => ['nik'],
        'gender' => ['gender', 'jenis kelamin'],
        'bank_name' => ['bank name', 'nama bank'],
        'bank_account_number' => ['bank account', 'bank account number', 'nomor rekening'],
        'bank_account_name' => ['bank account holder', 'bank account name', 'nama pemilik rekening'],
        'daily_rate' => ['daily rate', 'tarif harian'],
    ];

    public function preview(UploadedFile $file): array
    {
        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
        } catch (ReaderException $exception) {
            throw ValidationException::withMessages([
                'employee_file' => 'File tidak dapat dibaca. Unggah file Excel .xlsx atau CSV yang valid.',
            ]);
        }

        if (! $reader instanceof Xlsx && ! $reader instanceof Csv) {
            throw ValidationException::withMessages([
                'employee_file' => 'Format file tidak didukung. Gunakan Excel .xlsx atau CSV.',
            ]);
        }

        try {
            $reader->setReadDataOnly(false);
            $spreadsheet = $reader->load($file->getRealPath());
        } catch (ReaderException $exception) {
            throw ValidationException::withMessages([
                'employee_file' => 'File Excel/CSV rusak atau tidak dapat dibaca.',
            ]);
        }

        try {
            $sheet = $spreadsheet->getSheet(0);
            $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
            $highestRow = $sheet->getHighestDataRow();

            if ($highestRow > self::MAX_ROWS + 1) {
                throw ValidationException::withMessages([
                    'employee_file' => 'Maksimal 500 baris karyawan dalam satu kali impor.',
                ]);
            }

            $columns = [];
            for ($column = 1; $column <= $highestColumn; $column++) {
                $header = $this->normalizeHeader($this->cellValue($sheet->getCell([$column, 1])));
                if ($header !== '') {
                    $columns[$header] = $column;
                }
            }

            $fieldColumns = $this->resolveColumns($columns);
            $missingFields = array_diff([
                'id_karyawan', 'name', 'email', 'phone', 'address', 'departemen',
                'employment_type', 'start_date', 'work_location', 'nik',
                'bank_name', 'bank_account_number', 'bank_account_name',
            ], array_keys($fieldColumns));
            if ($missingFields !== []) {
                throw ValidationException::withMessages([
                    'employee_file' => 'Kolom wajib belum ditemukan: '.implode(', ', $missingFields).'. Gunakan template impor atau sesuaikan judul kolom.',
                ]);
            }

            $usesOrganization = isset($columns['organization'])
                && ($fieldColumns['departemen'] ?? null) === $columns['organization'];
            $rows = [];
            $errors = [];
            $seenEmails = [];
            $seenEmployeeIds = [];

            for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
                $row = [];
                $hasData = false;
                $hasFormula = false;
                $textErrors = [];

                foreach ($fieldColumns as $field => $column) {
                    $cell = $sheet->getCell([$column, $rowNumber]);
                    if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                        $row[$field] = null;
                        $hasFormula = true;

                        continue;
                    }

                    if (in_array($field, ['id_karyawan', 'phone', 'nik', 'bank_account_number'], true)
                        && $cell->getDataType() === DataType::TYPE_NUMERIC) {
                        $textErrors[] = match ($field) {
                            'id_karyawan' => 'Employee ID harus disimpan sebagai teks agar angka nol tidak hilang.',
                            'phone' => 'Nomor telepon harus disimpan sebagai teks agar angka nol tidak hilang.',
                            'nik' => 'NIK harus disimpan sebagai teks agar semua 16 digit tetap akurat.',
                            'bank_account_number' => 'Nomor rekening harus disimpan sebagai teks agar angka nol tidak hilang.',
                        };
                    }

                    $value = $this->cellValue($cell, $field === 'start_date', $field);
                    $row[$field] = $value;
                    $hasData = $hasData || $value !== null;
                }

                if (! $hasData) {
                    continue;
                }

                $row['email'] = strtolower(trim((string) ($row['email'] ?? '')));
                $row['id_karyawan'] = trim((string) ($row['id_karyawan'] ?? ''));
                $row['name'] = trim((string) ($row['name'] ?? ''));
                $row['departemen'] = trim((string) ($row['departemen'] ?? ''));
                $row['phone'] = trim((string) ($row['phone'] ?? ''));
                $row['address'] = trim((string) ($row['address'] ?? ''));
                $row['nik'] = trim((string) ($row['nik'] ?? ''));
                $row['bank_account_number'] = trim((string) ($row['bank_account_number'] ?? ''));
                $row['bank_account_name'] = trim((string) ($row['bank_account_name'] ?? ''));
                $row['bank_name'] = strtoupper(trim((string) ($row['bank_name'] ?? '')));
                $row['gender'] = $this->normalizeGender($row['gender'] ?? null);
                $row['employment_type'] = $this->normalizeEmploymentType($row['employment_type'] ?? null);
                $row['work_location'] = $this->normalizeWorkLocation($row['work_location'] ?? null);
                $row['daily_rate'] = $this->normalizeDailyRate($row['daily_rate'] ?? null);

                $validator = Validator::make($row, [
                    'name' => ['required', 'string', 'max:255'],
                    'gender' => ['nullable', 'in:male,female'],
                    'email' => ['required', 'string', 'email', 'max:255'],
                    'id_karyawan' => ['required', 'string', 'max:255'],
                    'phone' => ['required', 'string', 'max:30'],
                    'address' => ['required', 'string', 'max:1000'],
                    'departemen' => ['required', 'string', 'max:255'],
                    'employment_type' => ['required', 'in:organik,freelance'],
                    'start_date' => ['required', 'date', 'before_or_equal:today'],
                    'work_location' => ['required', 'in:office,produksi'],
                    'nik' => ['required', 'digits:16'],
                    'bank_name' => ['required', 'in:BCA,BRI'],
                    'bank_account_number' => ['required', 'string', 'max:40'],
                    'bank_account_name' => ['required', 'string', 'max:255'],
                    'daily_rate' => ['required_if:employment_type,freelance', 'nullable', 'integer', 'min:1', 'max:100000000'],
                ], [
                    'name.required' => 'Nama lengkap wajib diisi.',
                    'email.required' => 'Email wajib diisi.',
                    'email.email' => 'Format email tidak valid.',
                    'id_karyawan.required' => 'Employee ID wajib diisi.',
                    'phone.required' => 'Nomor telepon wajib diisi.',
                    'address.required' => 'Alamat wajib diisi.',
                    'departemen.required' => 'Departemen wajib diisi.',
                    'employment_type.in' => 'Jenis karyawan harus Organik atau Freelance.',
                    'start_date.required' => 'Join Date wajib diisi.',
                    'start_date.before_or_equal' => 'Join Date tidak boleh di masa depan.',
                    'work_location.in' => 'Lokasi kerja harus office atau produksi.',
                    'nik.digits' => 'NIK harus tepat 16 digit.',
                    'bank_name.in' => 'Bank hanya mendukung BCA atau BRI.',
                    'daily_rate.required_if' => 'Tarif harian wajib diisi untuk karyawan freelance.',
                    'daily_rate.integer' => 'Tarif harian harus berupa angka bulat tanpa simbol mata uang.',
                ]);
                $rowErrors = $validator->errors()->all();
                if ($hasFormula) {
                    $rowErrors[] = 'Rumus Excel tidak didukung; gunakan nilai tetap pada semua kolom.';
                }
                array_push($rowErrors, ...$textErrors);

                $emailKey = $row['email'];
                $employeeIdKey = strtolower($row['id_karyawan']);
                if ($emailKey !== '') {
                    if (isset($seenEmails[$emailKey])) {
                        $rowErrors[] = 'Email duplikat di dalam file (baris '.$seenEmails[$emailKey].').';
                    } else {
                        $seenEmails[$emailKey] = $rowNumber;
                    }
                    if (User::query()->whereRaw('LOWER(email) = ?', [$emailKey])->exists()) {
                        $rowErrors[] = 'Email sudah terdaftar di sistem.';
                    }
                }
                if ($employeeIdKey !== '') {
                    if (isset($seenEmployeeIds[$employeeIdKey])) {
                        $rowErrors[] = 'Employee ID duplikat di dalam file (baris '.$seenEmployeeIds[$employeeIdKey].').';
                    } else {
                        $seenEmployeeIds[$employeeIdKey] = $rowNumber;
                    }
                    if (User::query()->whereRaw('LOWER(id_karyawan) = ?', [$employeeIdKey])->exists()) {
                        $rowErrors[] = 'Employee ID sudah terdaftar di sistem.';
                    }
                }

                if ($rowErrors !== []) {
                    $errors[] = ['row' => $rowNumber, 'messages' => array_values(array_unique($rowErrors))];

                    continue;
                }

                $rows[] = $row;
            }

            if ($rows === [] && $errors === []) {
                throw ValidationException::withMessages([
                    'employee_file' => 'File tidak berisi baris karyawan untuk diimpor.',
                ]);
            }

            return [
                'rows' => $rows,
                'errors' => $errors,
                'uses_organization' => $usesOrganization,
            ];
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private function resolveColumns(array $columns): array
    {
        $resolved = [];
        foreach (self::HEADER_ALIASES as $field => $aliases) {
            foreach ($aliases as $alias) {
                if (isset($columns[$alias])) {
                    $resolved[$field] = $columns[$alias];
                    break;
                }
            }
        }

        return $resolved;
    }

    private function normalizeHeader(?string $value): string
    {
        return strtolower(trim((string) preg_replace('/\s+/', ' ', str_replace('_', ' ', (string) $value))));
    }

    private function cellValue(Cell $cell, bool $date = false, ?string $field = null): ?string
    {
        $value = $cell->getValue();
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        if ($field === 'daily_rate' && is_numeric($value)) {
            return (string) $value;
        }

        if ($date && is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (Exception) {
                return (string) $value;
            }
        }

        return trim((string) $cell->getFormattedValue());
    }

    private function normalizeEmploymentType(?string $value): ?string
    {
        return match (strtolower(trim((string) $value))) {
            'organik', 'organic' => 'organik',
            'freelance' => 'freelance',
            default => $value === null || trim($value) === '' ? null : strtolower(trim($value)),
        };
    }

    private function normalizeWorkLocation(?string $value): ?string
    {
        return match (strtolower(trim((string) $value))) {
            'office', 'kantor' => 'office',
            'produksi', 'production' => 'produksi',
            default => $value === null || trim($value) === '' ? null : strtolower(trim($value)),
        };
    }

    private function normalizeGender(?string $value): ?string
    {
        return match (strtolower(trim((string) $value))) {
            'male', 'laki-laki', 'laki laki' => 'male',
            'female', 'perempuan' => 'female',
            default => $value === null || trim($value) === '' ? null : strtolower(trim($value)),
        };
    }

    private function normalizeDailyRate(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
