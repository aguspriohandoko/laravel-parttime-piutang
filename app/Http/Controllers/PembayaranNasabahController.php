<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\SuratUsulan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PembayaranNasabahController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index()
    {
        $skrd = SuratUsulan::where('users_id', '=', Auth::user()->id)->groupby('nomor_surat')->get(['id', 'nomor_surat']);
        return view('nasabah.pembayaran', compact('skrd'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'usulans_id' => 'required',
            'penanggung_jawab' => 'required',
            'tgl_bayar' => 'required',
            'nominal_bayar' => 'required',
            'docs_bayar' => 'required|mimes:pdf'
        ], [
            'usulans_id.required' => 'Pilih Salah Satu No SKRD',
            'nominal_bayar.required' => 'Nominal Pembayaran Harus Di Isi',
            'tgl_bayar.required' => 'Tanggal Pembayaran Harus Di Isi',
            'penanggung_jawab.required' => 'Nama Penanggung Jawab Pembayaran Harus Di Isi',
            'docs_bayar.mimes' => 'Dokumen Pembayaran Hanya Boleh Format PDF',
            'docs_bayar.required' => 'Dokumen Pembyaran Harus Di Isi'
        ]);

        $file = $request->file('docs_bayar');
        $docsName = Str::random(5) . '.' . $file->getClientOriginalExtension();
        $file->storeAs('public/pembayaran/', $docsName);
        $usulan_id=$request->usulans_id;
        $usulan=SuratUsulan::find($usulan_id);

        $data=SuratUsulan::where('nomor_surat',$usulan->nomor_surat)->get();
        // dd($data);
        if(count($data) > 0){
            foreach ($data as $item) {
                Pembayaran::with('usulan')->create([
                    'usulans_id' => $item->id,
                    'penanggung_jawab' => $request->penanggung_jawab,
                    'tgl_bayar' => Carbon::now(),
                    'nominal_bayar' => str_replace('.', '', $request->nominal_bayar),
                    'docs_bayar' => $docsName
                ]);
            }
        }
        return back()->with(['success' => 'Berhasil Melakukan Pembayaran Dengan Nomor Surat Piutang ' . $usulan->nomor_surat]);
    }
}
