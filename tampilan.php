<?php
require_once 'koneksi.php';
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function rupiah($angka) { return 'Rp ' . number_format((float)$angka, 0, ',', '.'); }
$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT t.*,p.nama_pelanggan,p.no_hp FROM transaksi t JOIN pelanggan p ON t.id_pelanggan=p.id_pelanggan WHERE t.id_transaksi=?");
$stmt->bind_param("i",$id); $stmt->execute();
$transaksi = $stmt->get_result()->fetch_assoc();
if (!$transaksi) { http_response_code(404); exit('Transaksi tidak ditemukan. <a href="index.php">Kembali</a>'); }
$detail = $conn->prepare("SELECT d.*,p.nama_produk FROM detail_transaksi d JOIN produk p ON d.id_produk=p.id_produk WHERE d.id_transaksi=?");
$detail->bind_param("i",$id); $detail->execute(); $items=$detail->get_result();
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Struk Ayyinns Coffee</title><link rel="stylesheet" href="assets/style.css"></head>
<body class="receipt-body"><div class="receipt-actions"><a class="button button-outline" href="index.php?page=transaksi">← Kembali</a><button class="button" onclick="window.print()">Cetak / Simpan PDF</button></div>
<article class="receipt"><div class="receipt-brand"><span class="brand-mark">a.</span><h1>ayyinns coffee</h1><p>DISCOVER YOUR STYLE</p><small>Good coffee, good mood.</small></div><div class="receipt-rule"></div><div class="receipt-meta"><span>No. Transaksi</span><b>#AY-<?= str_pad($id,4,'0',STR_PAD_LEFT) ?></b><span>Tanggal</span><b><?= date('d/m/Y H:i',strtotime($transaksi['tanggal'])) ?></b><span>Pelanggan</span><b><?= e($transaksi['nama_pelanggan']) ?></b><?php if($transaksi['no_hp']): ?><span>No. HP</span><b><?= e($transaksi['no_hp']) ?></b><?php endif; ?></div><div class="receipt-rule dashed"></div><table class="receipt-table"><thead><tr><th>Item</th><th>Qty</th><th>Total</th></tr></thead><tbody><?php while($item=$items->fetch_assoc()): ?><tr><td><?= e($item['nama_produk']) ?><small><?= rupiah($item['harga_satuan']) ?>/item</small></td><td><?= $item['jumlah'] ?></td><td><?= rupiah($item['subtotal']) ?></td></tr><?php endwhile; ?></tbody></table><div class="receipt-rule dashed"></div><div class="receipt-total"><span>TOTAL PEMBAYARAN</span><strong><?= rupiah($transaksi['total']) ?></strong></div><div class="receipt-thanks"><div>✦</div><b>Thank you for stopping by!</b><p>Semoga harimu seenak kopi kami.</p><small>Follow the vibe · Come back soon</small></div></article><script>window.addEventListener('load',()=>{ /* Cetak manual lewat tombol di atas */ });</script></body></html>
