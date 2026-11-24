@extends('layouts.pmp')
@section('title', 'Add Request Bulk')
@section('crumb', 'Account Opening > Account Opening > Add Request Bulk')

@section('content')
<style>
    .toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:10px 0 8px}
    .chips{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
    .chip{border:1px solid var(--line);background:#fff;border-radius:16px;padding:4px 12px;font:inherit;font-size:12px;cursor:pointer;color:#46506b}
    .chip:hover{background:#f4f6fb}
    .chip.on{background:var(--blue);border-color:var(--blue);color:#fff}
    .chip.err{border-color:#f3b8c0;color:var(--err);background:#fff6f7}
    .chip.err.on{background:var(--err);border-color:var(--err);color:#fff}
    .chip.warn{border-color:#ecd9a6;color:var(--warn);background:#fffaf0}
    .chip.warn.on{background:var(--warn);border-color:var(--warn);color:#fff}
    .sumry{display:flex;gap:6px;flex-wrap:wrap;align-items:center;margin:6px 0 10px;font-size:12px}
    .bulkfix{background:#f4f7ff;border:1px solid #cfdcff;border-radius:6px;padding:10px 12px;margin:0 0 10px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;font-size:12.5px}
    .bulkfix select,.bulkfix input{width:240px;padding:6px 8px}
    tr.pinned td.sticky{box-shadow:inset 3px 0 0 var(--ok)}
</style>
<div x-data="bulkApp(@js($config))" x-cloak>
    <div class="head">
        <h1 class="title" style="margin:0">Add Request Bulk <span class="new">Autofill APR</span></h1>
        <div class="actions">
            <a class="btn" href="{{ route('ao.template') }}">&#8681; Template Excel (cara manual)</a>
            <a class="btn" href="{{ route('ao.index') }}">Cancel</a>
        </div>
    </div>

    <template x-if="cfg.extractor === 'mock'">
        <div class="alert info">Mode <b>mock</b>: data dibaca dari contoh, bukan dari isi PDF. Set <span class="mono">PMP_EXTRACTOR=claude</span> dan <span class="mono">ANTHROPIC_API_KEY</span> di <span class="mono">.env</span> untuk membaca PDF scan sungguhan.</div>
    </template>

    {{-- 1. UPLOAD --}}
    <div class="card">
        <h2>1. Upload Dokumen APR Nasabah</h2>
        <p class="hint" x-show="mode === 'single'">Satu PDF = satu nasabah (boleh lebih dari satu halaman). Pilih banyak file sekaligus. PDF, maksimal 5MB per file.</p>
        <p class="hint" x-show="mode === 'merged'">Satu PDF berisi banyak APR. Sistem memisahkannya di browser (file besar tidak dikirim utuh), tiap APR menjadi satu baris dan satu PDF sendiri, lalu diberi nama sesuai nama nasabah. Maksimal 5MB per APR.</p>

        <div style="display:flex;gap:18px;align-items:center;flex-wrap:wrap;margin:6px 0 10px;font-size:13px">
            <label style="display:flex;gap:6px;align-items:center"><input type="radio" value="single" x-model="mode"> 1 APR per file</label>
            <label style="display:flex;gap:6px;align-items:center"><input type="radio" value="merged" x-model="mode"> Gabungan: 1 PDF berisi banyak APR</label>
            <label x-show="mode === 'merged'" style="display:flex;gap:6px;align-items:center">Jumlah halaman <b>per 1 APR</b> (bukan jumlah APR)
                <input type="number" min="1" max="20" x-model.number="pagesPer" style="width:64px;padding:4px 6px"></label>
            <span x-show="mode === 'merged' && (pagesPer > 20 || pagesPer < 1)" style="color:var(--err);font-size:12px">Isi 1 sampai 20. Contoh: tiap nasabah 2 halaman, isi 2.</span>
        </div>

        <div class="dz" :class="{over: dragOver}"
             @click="$refs.picker.click()"
             @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false"
             @drop.prevent="dragOver = false; addFiles($event.dataTransfer.files)">
            <div class="ic">&#9729;</div>
            <div><b>Select files or drag and drop here</b></div>
            <small>pdf, size no more than 5MB per file</small>
            <input type="file" accept="application/pdf" multiple x-ref="picker" style="display:none"
                   @change="addFiles($event.target.files); $event.target.value = ''">
        </div>

        <template x-for="sp in splits" :key="sp.id">
            <div class="legend" style="margin-top:10px"><span>&#9986; Memisahkan <b x-text="sp.name"></b>: <b x-text="sp.done"></b> / <span x-text="sp.total || '?'"></span> APR</span>
                <span x-show="sp.note" style="color:var(--warn)" x-text="sp.note"></span></div>
        </template>

        <template x-if="files.length">
            <div>
                <div class="bar"><i :style="'width:' + progress + '%'"></i></div>
                <div class="legend">
                    <span><b x-text="doneCount"></b> / <span x-text="files.length"></span> file selesai dibaca</span>
                    <span x-show="failCount" style="color:var(--err)"><b x-text="failCount"></b> gagal</span>
                    <a href="#" @click.prevent="clearFiles()" x-show="!busy">Bersihkan daftar file</a>
                </div>
                <div class="files">
                    <template x-for="f in files" :key="f.id">
                        <div class="file">
                            <div class="n"><span>&#128196;</span><span x-text="f.name"></span><span class="dim" x-text="(f.size/1024).toFixed(0) + ' KB'"></span></div>
                            <div>
                                <span class="pill gray" x-show="f.status==='queue'">Antre</span>
                                <span class="pill blue" x-show="f.status==='processing'">Membaca...</span>
                                <span class="pill ok" x-show="f.status==='done'">Selesai</span>
                                <span class="pill err" x-show="f.status==='fail'" :title="f.msg" x-text="'Gagal: ' + f.msg"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    {{-- 2. HASIL --}}
    <div class="card" x-show="rows.length">
        <h2>2. Hasil Ekstraksi <span class="dim" style="font-weight:400">(Informasi Data Diri &amp; Informasi Pekerjaan)</span></h2>
        <p class="hint">Periksa dan koreksi langsung di tabel. Sel merah = tidak lolos aturan template PMP (wajib diperbaiki). Sel kuning = perlu dicek: tulisan APR diragukan, <b>diisi dari Dukcapil</b> (dicari otomatis berdasarkan NIK), atau berbeda dengan Dukcapil (tersedia tombol <b>Pakai Dukcapil</b>). Jika NIK dikoreksi, data Dukcapil diambil ulang. Jabatan diisi Staff untuk semua baris; ubah per baris bila perlu.</p>

        <div class="legend">
            <span><i style="background:var(--err-bg);border:1px solid var(--err)"></i>Salah / wajib diisi</span>
            <span><i style="background:var(--warn-bg);border:1px solid var(--warn)"></i>Perlu dicek</span>
            <span><b x-text="rows.length"></b> baris &middot; <b x-text="errorRows" style="color:var(--err)"></b> bermasalah &middot; <b x-text="warnRows"></b> perlu dicek</span>
        </div>

        <div class="toolbar">
            <div class="chips">
                <button class="chip" :class="{on: filter==='all'}" @click="setFilter('all')">Semua (<span x-text="rows.length"></span>)</button>
                <button class="chip err" :class="{on: filter==='err'}" @click="setFilter('err')">Bermasalah (<span x-text="errorRows"></span>)</button>
                <button class="chip warn" :class="{on: filter==='warn'}" @click="setFilter('warn')">Perlu dicek (<span x-text="warnRows"></span>)</button>
                <button class="chip" :class="{on: filter==='ok'}" @click="setFilter('ok')">OK (<span x-text="okRows"></span>)</button>
                <a href="#" x-show="pinnedCount" @click.prevent="hideResolved()" style="margin-left:6px;font-size:12px">Sembunyikan <span x-text="pinnedCount"></span> baris yang sudah beres</a>
            </div>
            <button class="btn sm primary" :disabled="!errorRows" @click="nextError()">Ke masalah berikutnya &#8595;</button>
        </div>

        <div class="sumry" x-show="errorByColumn.length">
            <span class="dim">Masalah per kolom:</span>
            <template x-for="e in errorByColumn" :key="e.col">
                <button class="chip err" :class="{on: colFilter===e.col}" :title="e.sample" @click="toggleCol(e.col)">
                    <span x-text="cfg.labels[e.col]"></span> &middot; <b x-text="e.count"></b>
                </button>
            </template>
            <a href="#" x-show="colFilter" @click.prevent="toggleCol(colFilter)">Hapus filter kolom</a>
        </div>
        <div class="dim" style="font-size:12px;margin:-4px 0 8px" x-show="colFilter">Pesan terbanyak: <span x-text="(errorByColumn.find(e => e.col === colFilter) || {}).sample"></span></div>

        <div class="bulkfix" x-show="colFilter && canBulkFix(colFilter)">
            <span>Isi semua <b x-text="colErrorCount"></b> sel bermasalah di kolom <b x-text="cfg.labels[colFilter]"></b> dengan:</span>
            <template x-if="cfg.selects[colFilter]">
                <select x-model="fixValue">
                    <option value="">- pilih -</option>
                    <template x-for="o in cfg.selects[colFilter]" :key="o.code"><option :value="o.code" x-text="o.text"></option></template>
                </select>
            </template>
            <template x-if="!cfg.selects[colFilter]">
                <input type="text" x-model="fixValue" placeholder="nilai pengganti">
            </template>
            <button class="btn sm" :disabled="!fixValue || fixing" @click="applyFix()" x-text="fixing ? 'Memproses...' : 'Terapkan'"></button>
        </div>

        <div class="dim" style="font-size:12px;margin-bottom:6px">Menampilkan <b x-text="rowsView.length"></b> dari <b x-text="rows.length"></b> baris</div>

        <div class="scroll">
            <table class="r">
                <thead>
                <tr>
                    <th class="sticky">#</th>
                    <th>APR</th>
                    <template x-for="c in cfg.columns" :key="c"><th x-text="cfg.labels[c]"></th></template>
                </tr>
                </thead>
                <tbody>
                <template x-for="item in rowsView" :key="item.row.uid">
                    <tr :class="{pinned: pinned[item.row.uid]}">
                        <td class="sticky">
                            <div><b x-text="item.n"></b></div>
                            <span class="pill err" x-show="status(item.row)==='err'">Cek</span>
                            <span class="pill warn" x-show="status(item.row)==='warn'">Cek?</span>
                            <span class="pill ok" x-show="status(item.row)==='ok'">OK</span>
                            <div style="margin-top:3px">
                                <span class="pill blue" x-show="item.row.dk==='found'" title="Data cocok ditemukan di Dukcapil berdasarkan NIK">DK &#10003;</span>
                                <span class="pill err" x-show="item.row.dk==='notfound'" title="NIK tidak ditemukan di Dukcapil">DK &#10007;</span>
                                <span class="pill warn" x-show="item.row.dk==='error'" title="Dukcapil tidak bisa dihubungi">DK ?</span>
                            </div>
                        </td>
                        <td style="min-width:120px">
                            <a :href="cfg.urls.pdf + '/' + item.row.token" target="_blank" x-text="item.row.filename" style="font-size:11.5px;word-break:break-all"></a><br>
                            <a href="#" @click.prevent="removeRow(item.row)" style="font-size:11px;color:var(--err)">Hapus baris</a>
                        </td>
                        <template x-for="c in cfg.columns" :key="c">
                            <td :data-cell="item.row.uid + ':' + c" :class="['c-' + c, item.row.errors[c] ? 'has-err' : (item.row.warnings[c] ? 'has-warn' : '')]">
                                <template x-if="cfg.selects[c]">
                                    <select x-model="item.row.values[c]" @change="revalidate(item.row)">
                                        <option value="">-</option>
                                        <template x-for="o in cfg.selects[c]" :key="o.code"><option :value="o.code" x-text="o.text"></option></template>
                                    </select>
                                </template>
                                <template x-if="!cfg.selects[c]">
                                    <input type="text" x-model="item.row.values[c]" @change="revalidate(item.row)">
                                </template>
                                <div class="msg" x-show="item.row.errors[c]" x-text="item.row.errors[c]"></div>
                                <div class="msg w" x-show="!item.row.errors[c] && item.row.warnings[c]" x-text="item.row.warnings[c]"></div>
                                <a href="#" class="msg w" style="display:block;text-decoration:underline"
                                   x-show="item.row.suggest[c] && item.row.values[c] !== item.row.suggest[c]"
                                   @click.prevent="item.row.values[c] = item.row.suggest[c]; revalidate(item.row)">Pakai Dukcapil</a>
                            </td>
                        </template>
                    </tr>
                </template>
                <tr x-show="!rowsView.length"><td colspan="40" class="empty">Tidak ada baris untuk filter ini.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- 3. MITRA + LAINNYA (sekali untuk semua) --}}
    <div class="card" x-show="rows.length">
        <h2>3. Informasi Mitra &amp; Informasi Lainnya <span class="dim" style="font-weight:400">(diisi sekali, berlaku untuk <b x-text="rows.length"></b> nasabah)</span></h2>

        <div class="sec">
            <div class="name">Informasi Mitra</div>
            <div class="grid">
                <div class="f span6"><label>Mitra <b>*</b></label>
                    <select x-model="mitra.id" @change="pickMitra()">
                        <option value="">Input Kode atau Nama Mitra</option>
                        <template x-for="m in cfg.mitras" :key="m.id"><option :value="m.id" x-text="m.id + ' - ' + m.name"></option></template>
                    </select>
                </div>
                <div class="f span3"><label>Nama Perusahaan</label><input type="text" disabled :value="mitra.name" placeholder="Nama Perusahaan"></div>
                <div class="f span3"><label>Kode Cabang</label><input type="text" disabled :value="cfg.branch"></div>
                <div class="f span6"><label>Pipeline ID</label><input type="text" disabled :value="mitra.pipeline_id" placeholder="Pipeline ID"></div>
                <div class="f span3"><label>Sektor Industri <b>*</b></label>
                    <select x-model="mitra.sektor"><option value="">Select...</option>
                        <template x-for="s in cfg.sektor" :key="s"><option :value="s" x-text="s"></option></template>
                    </select>
                </div>
                <div class="f span3"><label>Kode Produk</label><input type="text" disabled :value="mitra.kode_produk" placeholder="Kode Produk"></div>
                <div class="f span6"><label>Biaya Tabungan</label><input type="text" disabled :value="mitra.biaya_tabungan" placeholder="Biaya Tabungan"></div>
                <div class="f span3"><label>Status Kerjasama</label><input type="text" disabled :value="mitra.status_kerjasama" placeholder="Status Kerjasama"></div>
                <div class="f span3"><label>Status Benefit</label><input type="text" disabled :value="mitra.status_benefit" placeholder="Status Benefit"></div>
            </div>
        </div>

        <div class="sec">
            <div class="name">Informasi Lainnya</div>
            <div>
                <div class="dim" style="margin-bottom:8px">Sarana komunikasi pribadi untuk tujuan pemasaran dan atau penawaran yang dipilih nasabah (berlaku untuk semua nasabah di request ini)</div>
                <template x-for="c in cfg.consent" :key="c.key">
                    <label class="check"><input type="checkbox" x-model="consent[c.key]"> <span x-text="c.label"></span></label>
                </template>
                <div class="dim" style="margin-top:6px">Kode CONSENT di Excel: <span class="mono" x-text="consentString"></span></div>
            </div>
        </div>
    </div>

    {{-- SUBMIT --}}
    <div class="card" x-show="rows.length">
        <div class="alert err" x-show="serverMsg" x-text="serverMsg"></div>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:20px">
            <label class="check"><input type="checkbox" x-model="agree"> Saya telah membaca dan menyetujui <a href="#" @click.prevent>syarat dan ketentuan</a> penggunaan.</label>
            <div style="display:flex;align-items:center;gap:12px">
                <span class="dim" x-show="blocker" x-text="blocker"></span>
                <button class="btn primary" :disabled="!canSubmit" @click="submit()" x-text="submitting ? 'Memproses...' : 'Submit & Kirim ke BOM (' + rows.length + ' nasabah)'"></button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pdf-lib.min.js') }}"></script>
<script>
function bulkApp(cfg) {
    let uid = 0;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    return {
        cfg, files: [], rows: [], dragOver: false, running: 0, MAX_PARALLEL: 2,
        mode: 'single', pagesPer: 2, splits: [],
        mitra: { id: '', name: '', pipeline_id: '', kode_produk: '', biaya_tabungan: '', status_kerjasama: '', status_benefit: '', sektor: '' },
        consent: { aplikasi_pesan: false, panggilan_suara: false, sms: false, email: false },
        agree: false, submitting: false, serverMsg: '',
        filter: 'all', colFilter: '', pinned: {}, fixValue: '', fixing: false, lastKey: '', lastIdx: -1,

        get busy() { return this.splits.length > 0 || this.files.some(f => f.status === 'queue' || f.status === 'processing'); },
        get doneCount() { return this.files.filter(f => f.status === 'done').length; },
        get failCount() { return this.files.filter(f => f.status === 'fail').length; },
        get progress() { return this.files.length ? Math.round((this.doneCount + this.failCount) / this.files.length * 100) : 0; },
        get errorRows() { return this.rows.filter(r => Object.keys(r.errors).length).length; },
        get warnRows() { return this.rows.filter(r => !Object.keys(r.errors).length && Object.keys(r.warnings).length).length; },
        get okRows() { return this.rows.filter(r => !Object.keys(r.errors).length && !Object.keys(r.warnings).length).length; },
        get pinnedCount() { return Object.keys(this.pinned).length; },
        get rowsView() {
            const out = [];
            this.rows.forEach((r, i) => {
                const e = Object.keys(r.errors).length, w = Object.keys(r.warnings).length;
                let show = true;
                if (this.filter === 'err') show = e > 0;
                else if (this.filter === 'warn') show = e === 0 && w > 0;
                else if (this.filter === 'ok') show = e === 0 && w === 0;
                if (show && this.colFilter) show = !!r.errors[this.colFilter];
                if (show || this.pinned[r.uid]) out.push({ row: r, n: i + 1 });
            });
            return out;
        },
        get errorByColumn() {
            const m = {};
            for (const r of this.rows) {
                for (const [c, msg] of Object.entries(r.errors)) {
                    m[c] = m[c] || { col: c, count: 0, msgs: {} };
                    m[c].count++;
                    m[c].msgs[msg] = (m[c].msgs[msg] || 0) + 1;
                }
            }
            return Object.values(m)
                .map(e => ({ col: e.col, count: e.count, sample: Object.entries(e.msgs).sort((a, b) => b[1] - a[1])[0][0] }))
                .sort((a, b) => b.count - a.count);
        },
        get colErrorCount() { return this.rows.filter(r => r.errors[this.colFilter]).length; },
        get consentString() { return ['aplikasi_pesan', 'panggilan_suara', 'sms', 'email'].map(k => this.consent[k] ? 'Y' : 'N').join(''); },
        get blocker() {
            if (this.busy) return 'Menunggu pembacaan file selesai';
            if (this.errorRows) return this.errorRows + ' baris masih bermasalah';
            if (!this.mitra.id) return 'Pilih Mitra';
            if (!this.mitra.sektor) return 'Pilih Sektor Industri';
            if (!this.agree) return 'Centang syarat dan ketentuan';
            return '';
        },
        get canSubmit() { return this.rows.length > 0 && !this.blocker && !this.submitting; },

        hideResolved() { this.pinned = {}; if (this.colFilter && !this.colErrorCount) this.colFilter = ''; },
        setFilter(f) { this.filter = f; this.pinned = {}; },
        toggleCol(c) { this.colFilter = this.colFilter === c ? '' : c; this.fixValue = ''; this.pinned = {}; },
        canBulkFix(c) { return !['nama', 'no_ktp', 'npwp', 'ibu_kandung', 'tgl_lhr', 'kota_lhr'].includes(c); },

        errCells() {
            const out = [];
            for (const it of this.rowsView) for (const c of this.cfg.columns) if (it.row.errors[c]) out.push(it.row.uid + ':' + c);
            return out;
        },
        nextError() {
            const L = this.errCells();
            if (!L.length) return;
            let idx = L.indexOf(this.lastKey);
            idx = idx >= 0 ? (idx + 1) % L.length : Math.min(Math.max(this.lastIdx, 0), L.length - 1);
            this.lastKey = L[idx]; this.lastIdx = idx;
            const el = document.querySelector('[data-cell="' + this.lastKey + '"]');
            if (el) {
                el.scrollIntoView({ block: 'center', inline: 'center', behavior: 'smooth' });
                const f = el.querySelector('input,select');
                if (f) f.focus({ preventScroll: true });
            }
        },

        async applyFix() {
            const col = this.colFilter, val = this.fixValue;
            const targets = this.rows.filter(r => r.errors[col]);
            if (!col || !val || !targets.length) return;
            this.fixing = true;
            targets.forEach(r => { r.values[col] = val; this.pinned[r.uid] = true; });
            for (let i = 0; i < targets.length; i += 8) {
                await Promise.all(targets.slice(i, i + 8).map(r => this.revalidate(r)));
            }
            this.fixValue = '';
            this.fixing = false;
        },

        status(r) { return Object.keys(r.errors).length ? 'err' : (Object.keys(r.warnings).length ? 'warn' : 'ok'); },

        addFiles(list) {
            for (const f of Array.from(list)) {
                const isPdf = /\.pdf$/i.test(f.name) || f.type === 'application/pdf';
                if (this.mode === 'merged' && isPdf) this.splitMerged(f);
                else this.addOne(f);
            }
        },
        addOne(f) {
            const item = { id: ++uid, file: f, name: f.name, size: f.size, status: 'queue', msg: '' };
            if (!/\.pdf$/i.test(f.name) && f.type !== 'application/pdf') { item.status = 'fail'; item.msg = 'bukan PDF'; }
            else if (f.size > 5 * 1024 * 1024) { item.status = 'fail'; item.msg = 'lebih dari 5MB'; }
            this.files.push(item);
            this.pump();
        },

        // Pisahkan 1 PDF gabungan menjadi PDF per APR (di browser, pakai pdf-lib), lalu masukkan ke antrean baca.
        async splitMerged(f) {
            const asked = parseInt(this.pagesPer) || 1;
            if (asked < 1 || asked > 20) {
                this.files.push({ id: ++uid, file: null, name: f.name, size: f.size, status: 'fail', msg: 'Halaman per APR harus 1 sampai 20 (kamu mengisi ' + asked + '). Isi jumlah halaman untuk SATU nasabah, bukan jumlah APR.' });
                return;
            }
            const per = asked;
            const sp = { id: ++uid, name: f.name, done: 0, total: 0, note: '' };
            this.splits.push(sp);
            const base = f.name.replace(/\.pdf$/i, '');
            try {
                if (!window.PDFLib) throw new Error('pdf-lib belum termuat');
                const src = await PDFLib.PDFDocument.load(new Uint8Array(await f.arrayBuffer()), { ignoreEncryption: true });
                const pages = src.getPageCount();
                sp.total = Math.ceil(pages / per);
                sp.note = pages + ' halaman / ' + per + ' per APR = ' + sp.total + ' APR';
                if (pages % per) sp.note += '. Tidak habis dibagi, APR terakhir hanya ' + (pages % per) + ' halaman';
                const pad = Math.max(3, String(sp.total).length);
                for (let i = 0; i < sp.total; i++) {
                    const idx = [];
                    for (let p = i * per; p < Math.min(pages, (i + 1) * per); p++) idx.push(p);
                    const out = await PDFLib.PDFDocument.create();
                    (await out.copyPages(src, idx)).forEach(pg => out.addPage(pg));
                    const bytes = await out.save();
                    const name = base + '_' + String(i + 1).padStart(pad, '0') + '.pdf';
                    this.addOne(new File([bytes], name, { type: 'application/pdf' }));
                    sp.done = i + 1;
                    await new Promise(r => setTimeout(r, 0)); // beri napas ke UI
                }
            } catch (e) {
                this.files.push({ id: ++uid, file: null, name: f.name, size: f.size, status: 'fail', msg: 'gagal dipisah: ' + e.message });
            } finally {
                this.splits = this.splits.filter(x => x.id !== sp.id);
            }
        },
        clearFiles() { this.files = []; },

        pump() {
            while (this.running < this.MAX_PARALLEL) {
                const next = this.files.find(f => f.status === 'queue');
                if (!next) return;
                next.status = 'processing';
                this.running++;
                this.readOne(next).finally(() => { this.running--; this.pump(); });
            }
        },

        async readOne(item) {
            const fd = new FormData();
            fd.append('file', item.file);
            try {
                const res = await fetch(cfg.urls.extract, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
                const j = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(j.errors?.file?.[0] || j.message || ('HTTP ' + res.status));
                this.rows.push({ uid: ++uid, token: j.token, filename: j.filename, values: j.values, errors: j.errors, warnings: j.warnings, suggest: j.suggest || {}, dk: j.dk || 'skip' });
                item.status = 'done';
            } catch (e) {
                item.status = 'fail';
                item.msg = e.message;
            }
        },

        async revalidate(row) {
            this.pinned[row.uid] = true; // baris tetap tampil walau sudah beres, sampai user menyegarkan daftar
            try {
                const res = await fetch(cfg.urls.validate, {
                    method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ values: row.values })
                });
                const j = await res.json();
                row.values = j.values; row.errors = j.errors; row.warnings = j.warnings; row.suggest = j.suggest || {}; row.dk = j.dk || 'skip';
            } catch (e) { /* abaikan; akan divalidasi ulang saat submit */ }
        },

        removeRow(row) { const i = this.rows.findIndex(r => r.uid === row.uid); if (i >= 0) this.rows.splice(i, 1); },

        pickMitra() {
            const m = cfg.mitras.find(x => x.id === this.mitra.id);
            if (m) { this.mitra = { ...m, sektor: m.sektor || this.mitra.sektor }; }
            else { this.mitra = { id: '', name: '', pipeline_id: '', kode_produk: '', biaya_tabungan: '', status_kerjasama: '', status_benefit: '', sektor: '' }; }
        },

        async submit() {
            this.serverMsg = ''; this.submitting = true;
            try {
                const res = await fetch(cfg.urls.submit, {
                    method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        mitra: this.mitra, consent: this.consent,
                        rows: this.rows.map(r => ({ values: r.values, token: r.token, filename: r.filename }))
                    })
                });
                const j = await res.json().catch(() => ({}));
                if (res.ok) { window.location = j.redirect; return; }
                if (j.row_errors) {
                    Object.entries(j.row_errors).forEach(([i, errs]) => { if (this.rows[i]) this.rows[i].errors = errs; });
                }
                this.serverMsg = j.message || ('Gagal submit (HTTP ' + res.status + ')');
            } catch (e) {
                this.serverMsg = 'Gagal submit: ' + e.message;
            } finally { this.submitting = false; }
        },
    };
}
</script>
@endpush
