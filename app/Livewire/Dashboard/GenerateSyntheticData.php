<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\User;
use App\Services\SyntheticDataImportService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class GenerateSyntheticData extends Component
{
    use WithFileUploads;

    public $jsonFile;
    public $selectedUserId;
    public $validationResult = null;
    public $importResult = null;
    public $isValidating = false;
    public $isImporting = false;

    public function mount()
    {
        // Check if user is admin (you may need to adjust this based on your admin check)
        if (!$this->isAdmin()) {
            abort(403, 'Unauthorized access');
        }
    }

    public function updatedJsonFile()
    {
        $this->validationResult = null;
        $this->importResult = null;
    }

    public function validateJson()
    {
        $this->validate([
            'jsonFile' => 'required|file|mimes:json|max:10240', // 10MB max
        ]);

        $this->isValidating = true;

        try {
            $content = $this->jsonFile->get();
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->validationResult = [
                    'valid' => false,
                    'errors' => ['Invalid JSON format: ' . json_last_error_msg()],
                    'warnings' => [],
                    'summary' => [],
                ];
                $this->isValidating = false;
                return;
            }

            $user = $this->getSelectedUser();
            if (!$user) {
                $this->validationResult = [
                    'valid' => false,
                    'errors' => ['Please select a user'],
                    'warnings' => [],
                    'summary' => [],
                ];
                $this->isValidating = false;
                return;
            }

            $service = new SyntheticDataImportService();
            $this->validationResult = $service->validate($data, $user);

        } catch (\Exception $e) {
            $this->validationResult = [
                'valid' => false,
                'errors' => ['Validation error: ' . $e->getMessage()],
                'warnings' => [],
                'summary' => [],
            ];
        } finally {
            $this->isValidating = false;
        }
    }

    public function importData()
    {
        if (!$this->validationResult || !$this->validationResult['valid']) {
            session()->flash('error', 'Please validate the JSON file first and fix any errors.');
            return;
        }

        $this->isImporting = true;

        try {
            $content = $this->jsonFile->get();
            $data = json_decode($content, true);

            $user = $this->getSelectedUser();
            if (!$user) {
                session()->flash('error', 'Please select a user');
                $this->isImporting = false;
                return;
            }

            $service = new SyntheticDataImportService();
            $this->importResult = $service->import($data, $user);

            if ($this->importResult['success']) {
                session()->flash('success', 'Data imported successfully!');
            } else {
                session()->flash('error', 'Import failed: ' . implode(', ', $this->importResult['errors']));
            }

        } catch (\Exception $e) {
            $this->importResult = [
                'success' => false,
                'imported' => [],
                'errors' => ['Import error: ' . $e->getMessage()],
            ];
            session()->flash('error', 'Import failed: ' . $e->getMessage());
        } finally {
            $this->isImporting = false;
        }
    }

    public function resetForm()
    {
        $this->jsonFile = null;
        $this->selectedUserId = null;
        $this->validationResult = null;
        $this->importResult = null;
        $this->resetValidation();
    }

    private function getSelectedUser(): ?User
    {
        if (!$this->selectedUserId) {
            return Auth::user();
        }

        return User::find($this->selectedUserId);
    }

    private function isAdmin(): bool
    {
        $user = Auth::user();
        return $user->isAdmin();
    }

    public function render()
    {
        $users = User::orderBy('name')->get();

        return view('livewire.dashboard.generate-synthetic-data', [
            'users' => $users,
        ]);
    }
}

