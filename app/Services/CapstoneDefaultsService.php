<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AssessmentStage;
use App\Models\AttendancePenaltyRule;
use App\Models\Module;

/**
 * Menyiapkan konfigurasi default (modul, stage+kriteria, penalty rules) untuk
 * sebuah tahun ajaran, berdasarkan config/capstone.php.
 */
class CapstoneDefaultsService
{
    public function seed(AcademicYear $ay): void
    {
        $this->seedModules($ay);
        $this->seedStages($ay);
        $this->seedPenaltyRules($ay);
    }

    public function seedModules(AcademicYear $ay): void
    {
        $fields = config('capstone.default_logbook_fields');

        foreach (config('capstone.default_modules') as $m) {
            Module::create([
                'academic_year_id' => $ay->id,
                'order_index' => $m['order'],
                'week_label' => $m['week_label'],
                'code' => $m['code'],
                'type' => $m['type'],
                'assessment_stage' => $m['stage'],
                'title' => $m['title'],
                'description' => $m['description'],
                'fields_json' => $m['type'] === 'assessment' ? [] : $fields,
            ]);
        }
    }

    public function seedStages(AcademicYear $ay): void
    {
        foreach (config('capstone.default_stages') as $s) {
            $stage = AssessmentStage::create([
                'academic_year_id' => $ay->id,
                'code' => $s['code'],
                'name' => $s['name'],
                'weight_percentage' => $s['weight'],
                'peer_weight_percentage' => $s['peer_weight'],
                'peer_open' => false,
                'order_index' => $s['order'],
            ]);

            foreach ($s['criteria'] as $i => $name) {
                $stage->criteria()->create(['name' => $name, 'order_index' => $i]);
            }
        }
    }

    public function seedPenaltyRules(AcademicYear $ay): void
    {
        foreach (config('capstone.default_penalty_rules') as $r) {
            AttendancePenaltyRule::create([
                'academic_year_id' => $ay->id,
                'label' => $r['label'],
                'min_days' => $r['min_days'],
                'max_days' => $r['max_days'],
                'penalty_type' => $r['type'],
                'deduction_points' => $r['points'],
            ]);
        }
    }
}
