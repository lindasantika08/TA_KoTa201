<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;

use App\Models\Major;
use App\Models\Prodi;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MajorAdminController extends Controller
{

    public function createMajor()
    {
        return Inertia::render('Admin/ManageMajor');
    }

    public function showMajor()
    {
        $majors = Major::all();

        return $majors->map(function ($major) {
            $studentCount = Mahasiswa::whereHas('classRoom.prodi.major', function ($query) use ($major) {
                $query->where('major.id', $major->id);
            })->count();

            return [
                'major_name' => $major->major_name,
                'student_count' => $studentCount,
                'updated_at' => $major->updated_at
            ];
        });
    }

    public function addMajor(Request $request)
    {
        $request->validate([
            'major_name' => 'required|string|max:255',
        ]);

        major::create([
            'major_name' => $request->major_name,
        ]);

        return response()->json(['message' => 'major added successfully!'], 201);
    }

    public function deleteMajor(Request $request)
    {
        $request->validate([
            'major_name' => 'required|string|max:255',
        ]);

        $major = Major::Where('major_name', $request->major_name)->first();

        if (!$major) {
            return response()->json(['message' => 'Major Not found!'], 404);
        }
        $major->delete();
        return response()->json(['message' => 'Major deleted successfully!'], 201);
    }

    public function editMajor(Request $request)
    {
        $request->validate([
            'old_major_name' => 'required|string|max:255',
            'new_major_name' => 'required|string|max:255',
        ]);

        $major = Major::where('major_name', $request->old_major_name)->first();

        if (!$major) {
            return response()->json(['message', 'Major not Found!', 404]);
        }

        $major->update(['major_name' => $request->new_major_name]);

        return response()->json(['message', 'edit succesfully!', 201]);
    }

    public function showManageProdi()
    {
        return Inertia::render('Admin/ManageProdi');
    }

    public function showProdi()
    {
        return Prodi::with('major')->get()->map(function ($prodi, $index) {
            return [
                'no' => $index + 1,
                'prodi_name' => $prodi->prodi_name,
                'major_name' => $prodi->major->major_name,
                'updated_at' => $prodi->updated_at
            ];
        });
    }

    public function showMajorDropDown()
    {
        try {
            $majors = Major::select('id', 'major_name')
                ->whereNull('deleted_at')
                ->get();

            return response()->json($majors);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch majors'], 500);
        }
    }

    public function addProdi(Request $request)
    {
        $request->validate([
            'major_name' => 'required|string|max:255',
            'prodi_name' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $major = Major::where('major_name', $request->major_name)->first();

            if (!$major) {
                DB::rollBack();
                return response()->json(['message' => 'Major not found!'], 404);
            }

            $prodi = Prodi::create([
                'major_id' => $major->id,
                'prodi_name' => $request->prodi_name
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Prodi added successfully',
                'data' => [
                    'prodi' => $prodi,
                    'major' => $major
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'failed added'], 500);
        }
    }

    public function deleteProdi(Request $request)
    {
        $request->validate([
            'prodi_name' => 'required|string|max:255',
        ]);

        $prodi = Prodi::Where('prodi_name', $request->prodi_name)->first();

        if (!$prodi) {
            return response()->json(['message' => 'prodi Not found!'], 404);
        }
        $prodi->delete();
        return response()->json(['message' => 'prodi deleted successfully!'], 201);
    }

    public function updateProdi(Request $request)
    {
        $request->validate([
            'old_major_name' => 'required|string|max:255',
            'old_prodi_name' => 'required|string|max:255',
            'new_major_name' => 'required|string|max:255',
            'new_prodi_name' => 'required|string|max:255',
        ]);

        $major = Major::where('major_name', $request->old_major_name)->first();

        if (!$major) {
            DB::rollBack();
            return response()->json(['message' => 'Major not found!'], 404);
        }

        $prodi = Prodi::Where('prodi_name', $request->old_prodi_name)->first();

        if (!$prodi) {
            DB::rollBack();
            return response()->json(['message' => 'Prodi not found!'], 404);
        }

        $prodi->update(['prodi_name' => $request->new_prodi_name]);
        return response()->json(['message' => 'prodi update successfully!'], 201);
    }
}
