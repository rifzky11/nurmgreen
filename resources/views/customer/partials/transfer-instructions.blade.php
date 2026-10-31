{{-- Instruksi pembayaran manual via transfer bank (dipakai di halaman sukses & detail pesanan) --}}
<div class="transfer-box">
    <h3>Instruksi Pembayaran</h3>
    <p>Silakan transfer sesuai total pembayaran ke rekening berikut:</p>
    <ul class="transfer-list">
        <li>Bank <strong>BCA</strong></li>
        <li>Nomor rekening <strong>1234567890</strong></li>
        <li>Atas nama <strong>NurmGreen</strong></li>
    </ul>
    <ol class="transfer-steps">
        <li>Transfer sesuai nominal <strong>Rp {{ number_format($order->total_price, 0, ',', '.') }}</strong>.</li>
        <li>Cantumkan kode pesanan <strong>{{ $order->order_code }}</strong> pada berita/keterangan transfer.</li>
        <li>Simpan bukti transfer. Pesanan diproses setelah pembayaran diverifikasi admin (maks. 1&times;24 jam kerja).</li>
    </ol>
</div>
