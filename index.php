<?php
require_once 'koneksi.php';
$page = $_GET['page'] ?? 'dashboard';
$allowed = ['dashboard','produk','pelanggan','transaksi'];
if (!in_array($page, $allowed, true)) $page = 'dashboard';

function rupiah($angka) { return 'Rp ' . number_format((float)$angka, 0, ',', '.'); }
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'tambah_produk') {
        $stmt = $conn->prepare("INSERT INTO produk (nama_produk,kategori,harga,stok) VALUES (?,?,?,?)");
        $stmt->bind_param("ssdi", $_POST['nama_produk'], $_POST['kategori'], $_POST['harga'], $_POST['stok']);
        $stmt->execute();
        header('Location: index.php?page=produk&status=sukses'); exit;
    }
    if ($aksi === 'tambah_pelanggan') {
        $stmt = $conn->prepare("INSERT INTO pelanggan (nama_pelanggan,no_hp,email) VALUES (?,?,?)");
        $stmt->bind_param("sss", $_POST['nama_pelanggan'], $_POST['no_hp'], $_POST['email']);
        $stmt->execute();
        header('Location: index.php?page=pelanggan&status=sukses'); exit;
    }
    if ($aksi === 'buat_transaksi') {
        $id_pelanggan = (int)($_POST['id_pelanggan'] ?? 0);
        $id_produk = (int)($_POST['id_produk'] ?? 0);
        $jumlah = max(1, (int)($_POST['jumlah'] ?? 1));
        $produkQ = $conn->prepare("SELECT nama_produk,harga,stok FROM produk WHERE id_produk=?");
        $produkQ->bind_param("i", $id_produk); $produkQ->execute();
        $produk = $produkQ->get_result()->fetch_assoc();
        $pelangganQ = $conn->prepare("SELECT id_pelanggan FROM pelanggan WHERE id_pelanggan=?");
        $pelangganQ->bind_param("i", $id_pelanggan); $pelangganQ->execute();
        if (!$produk || !$pelangganQ->get_result()->fetch_assoc()) {
            header('Location: index.php?page=transaksi&status=gagal'); exit;
        }
        if ((int)$produk['stok'] < $jumlah) {
            header('Location: index.php?page=transaksi&status=stok'); exit;
        }
        $total = (float)$produk['harga'] * $jumlah;
        $conn->begin_transaction();
        try {
            $ins = $conn->prepare("INSERT INTO transaksi (id_pelanggan,total) VALUES (?,?)");
            $ins->bind_param("id", $id_pelanggan, $total); $ins->execute();
            $id_transaksi = $conn->insert_id;
            $detail = $conn->prepare("INSERT INTO detail_transaksi (id_transaksi,id_produk,jumlah,harga_satuan,subtotal) VALUES (?,?,?,?,?)");
            $detail->bind_param("iiidd", $id_transaksi, $id_produk, $jumlah, $produk['harga'], $total); $detail->execute();
            $up = $conn->prepare("UPDATE produk SET stok=stok-? WHERE id_produk=?");
            $up->bind_param("ii", $jumlah, $id_produk); $up->execute();
            $conn->commit();
            header('Location: tampilan.php?id=' . $id_transaksi); exit;
        } catch (Throwable $ex) {
            $conn->rollback();
            header('Location: index.php?page=transaksi&status=gagal'); exit;
        }
    }
}

$produk = $conn->query("SELECT * FROM produk ORDER BY id_produk DESC");
$pelanggan = $conn->query("SELECT * FROM pelanggan ORDER BY id_pelanggan DESC");
$transaksi = $conn->query("SELECT t.*,p.nama_pelanggan FROM transaksi t JOIN pelanggan p ON t.id_pelanggan=p.id_pelanggan ORDER BY t.id_transaksi DESC LIMIT 8");
$totalProduk = $conn->query("SELECT COUNT(*) n FROM produk")->fetch_assoc()['n'];
$totalPelanggan = $conn->query("SELECT COUNT(*) n FROM pelanggan")->fetch_assoc()['n'];
$totalTransaksi = $conn->query("SELECT COUNT(*) n FROM transaksi")->fetch_assoc()['n'];
$totalOmzet = $conn->query("SELECT COALESCE(SUM(total),0) n FROM transaksi")->fetch_assoc()['n'];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ayyinns Coffee — Discover Your Style</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<aside class="sidebar">
  <a class="brand" href="index.php"><span class="brand-mark">a.</span><span><b>ayyinns</b><small>COFFEE HOUSE</small></span></a>
  <div class="side-label">WORKSPACE</div>
  <nav>
    <a class="<?= $page==='dashboard'?'active':'' ?>" href="index.php"><span>◫</span> Dashboard</a>
    <a class="<?= $page==='produk'?'active':'' ?>" href="index.php?page=produk"><span>◈</span> Data Produk</a>
    <a class="<?= $page==='pelanggan'?'active':'' ?>" href="index.php?page=pelanggan"><span>♙</span> Data Pelanggan</a>
    <a class="<?= $page==='transaksi'?'active':'' ?>" href="index.php?page=transaksi"><span>▤</span> Transaksi</a>
  </nav>
  <div class="sidebar-bottom"><div class="mini-logo">✦</div><b>Good coffee, good mood.</b><p>Temukan rasa yang jadi gayamu.</p></div>
</aside>
<main class="main">
<header class="topbar"><div><div class="eyebrow">MONDAY — EVERYDAY IS A COFFEE DAY</div><h1><?= ['dashboard'=>'Dashboard','produk'=>'Data Produk','pelanggan'=>'Data Pelanggan','transaksi'=>'Transaksi'][$page] ?></h1></div><div class="user-pill"><span class="avatar">A</span><span><b>Ayyinns Admin</b><small>Store manager</small></span></div></header>
<?php if(isset($_GET['status'])): ?><div class="notice"><?= $_GET['status']==='sukses'?'Data berhasil ditambahkan.':($_GET['status']==='stok'?'Stok produk tidak mencukupi.':'Transaksi gagal. Periksa data dan coba lagi.') ?></div><?php endif; ?>

<?php if($page==='dashboard'): ?>
<section class="hero"><div class="hero-copy"><span class="pill-light">✦ YOUR DAILY LITTLE ESCAPE</span><h2>Discover<br><em>Your Style.</em></h2><p>Secangkir kebahagiaan, satu cerita di setiap tegukan.</p><a class="button button-light" href="index.php?page=transaksi">＋ Buat transaksi</a></div><div class="hero-art"><div class="sun"></div><div class="coffee-cup"><div class="coffee-top">☕</div></div><span class="art-caption">SLOW DOWN & SIP</span></div></section>
<section class="stats">
 <article class="stat-card"><div class="stat-icon peach">◈</div><span>Total Produk</span><strong><?= $totalProduk ?></strong><small>Item dalam katalog</small></article>
 <article class="stat-card"><div class="stat-icon lilac">♙</div><span>Total Pelanggan</span><strong><?= $totalPelanggan ?></strong><small>Teman Ayyinns</small></article>
 <article class="stat-card"><div class="stat-icon mint">▤</div><span>Total Transaksi</span><strong><?= $totalTransaksi ?></strong><small>Transaksi tercatat</small></article>
 <article class="stat-card"><div class="stat-icon gold">↗</div><span>Total Pendapatan</span><strong class="revenue"><?= rupiah($totalOmzet) ?></strong><small>Akumulasi penjualan</small></article>
</section>
<section class="content-card"><div class="section-head"><div><h3>Transaksi terbaru</h3><p>Pantau pesanan yang baru masuk.</p></div><a class="text-link" href="index.php?page=transaksi">Lihat semua ↗</a></div>
<div class="table-wrap"><table><thead><tr><th>ID TRANSAKSI</th><th>PELANGGAN</th><th>TANGGAL</th><th>TOTAL</th><th>STATUS</th><th></th></tr></thead><tbody>
<?php if($transaksi->num_rows): while($t=$transaksi->fetch_assoc()): ?><tr><td><b>#AY-<?= str_pad($t['id_transaksi'],4,'0',STR_PAD_LEFT) ?></b></td><td><?= e($t['nama_pelanggan']) ?></td><td><?= date('d M Y, H:i',strtotime($t['tanggal'])) ?></td><td><b><?= rupiah($t['total']) ?></b></td><td><span class="status">Selesai</span></td><td><a class="icon-link" href="tampilan.php?id=<?= $t['id_transaksi'] ?>">↗ Struk</a></td></tr><?php endwhile; else: ?><tr><td colspan="6" class="empty">Belum ada transaksi. Yuk, buat transaksi pertama!</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php elseif($page==='produk'): ?>
<section class="content-card"><div class="section-head"><div><h3>Katalog produk</h3><p>Kelola menu minuman dan camilan.</p></div><span class="count"><?= $produk->num_rows ?> item</span></div>
<form class="form-grid" method="post"><input type="hidden" name="aksi" value="tambah_produk"><label>Nama produk<input required name="nama_produk" placeholder="Contoh: Iced Matcha Latte"></label><label>Kategori<select name="kategori"><option>Minuman</option><option>Camilan</option><option>Pastry</option></select></label><label>Harga (Rp)<input required type="number" min="0" name="harga" placeholder="25000"></label><label>Stok<input required type="number" min="0" name="stok" placeholder="20"></label><button class="button" type="submit">＋ Tambah produk</button></form>
<div class="table-wrap"><table><thead><tr><th>ID</th><th>NAMA PRODUK</th><th>KATEGORI</th><th>HARGA</th><th>STOK</th></tr></thead><tbody><?php if($produk->num_rows):while($p=$produk->fetch_assoc()): ?><tr><td>#<?= $p['id_produk'] ?></td><td><b><?= e($p['nama_produk']) ?></b></td><td><span class="tag"><?= e($p['kategori']) ?></span></td><td><?= rupiah($p['harga']) ?></td><td><?= $p['stok'] ?></td></tr><?php endwhile; else: ?><tr><td colspan="5" class="empty">Belum ada produk.</td></tr><?php endif; ?></tbody></table></div></section>
<?php elseif($page==='pelanggan'): ?>
<section class="content-card"><div class="section-head"><div><h3>Daftar pelanggan</h3><p>Kenali teman yang datang ke Ayyinns.</p></div><span class="count"><?= $pelanggan->num_rows ?> pelanggan</span></div>
<form class="form-grid" method="post"><input type="hidden" name="aksi" value="tambah_pelanggan"><label>Nama pelanggan<input required name="nama_pelanggan" placeholder="Nama lengkap"></label><label>No. HP<input name="no_hp" placeholder="08xxxxxxxxxx"></label><label>Email<input type="email" name="email" placeholder="nama@email.com"></label><button class="button" type="submit">＋ Tambah pelanggan</button></form>
<div class="table-wrap"><table><thead><tr><th>ID</th><th>NAMA PELANGGAN</th><th>NO. HP</th><th>EMAIL</th></tr></thead><tbody><?php if($pelanggan->num_rows):while($c=$pelanggan->fetch_assoc()): ?><tr><td>#<?= $c['id_pelanggan'] ?></td><td><b><?= e($c['nama_pelanggan']) ?></b></td><td><?= e($c['no_hp']) ?></td><td><?= e($c['email']) ?></td></tr><?php endwhile; else: ?><tr><td colspan="4" class="empty">Belum ada pelanggan.</td></tr><?php endif; ?></tbody></table></div></section>
<?php else: ?>
<section class="content-card"><div class="section-head"><div><h3>Buat transaksi baru</h3><p>Pilih pelanggan, menu, dan jumlah pembelian.</p></div></div>
<form class="transaction-form" method="post"><input type="hidden" name="aksi" value="buat_transaksi"><label>Pelanggan<select name="id_pelanggan" required><option value="">Pilih pelanggan...</option><?php $pelanggan->data_seek(0);while($c=$pelanggan->fetch_assoc()): ?><option value="<?= $c['id_pelanggan'] ?>"><?= e($c['nama_pelanggan']) ?></option><?php endwhile; ?></select></label><label>Produk<select name="id_produk" required><?php $produk->data_seek(0);while($p=$produk->fetch_assoc()): ?><option value="<?= $p['id_produk'] ?>" <?= $p['stok']<1?'disabled':'' ?>><?= e($p['nama_produk']) ?> — <?= rupiah($p['harga']) ?> (stok <?= $p['stok'] ?>)</option><?php endwhile; ?></select></label><label>Jumlah<input type="number" name="jumlah" min="1" value="1" required></label><button class="button" type="submit">Selesaikan & cetak struk ↗</button></form>
<p class="hint">Catatan: tambahkan data pelanggan dan produk terlebih dahulu. Setelah transaksi berhasil, halaman struk akan terbuka dan bisa dicetak.</p></section>
<section class="content-card"><div class="section-head"><div><h3>Riwayat transaksi</h3><p>Semua transaksi terbaru.</p></div></div><div class="table-wrap"><table><thead><tr><th>ID</th><th>PELANGGAN</th><th>TANGGAL</th><th>TOTAL</th><th>STRUK</th></tr></thead><tbody><?php $all=$conn->query("SELECT t.*,p.nama_pelanggan FROM transaksi t JOIN pelanggan p ON t.id_pelanggan=p.id_pelanggan ORDER BY t.id_transaksi DESC"); if($all->num_rows):while($t=$all->fetch_assoc()): ?><tr><td>#AY-<?= str_pad($t['id_transaksi'],4,'0',STR_PAD_LEFT) ?></td><td><?= e($t['nama_pelanggan']) ?></td><td><?= date('d M Y H:i',strtotime($t['tanggal'])) ?></td><td><b><?= rupiah($t['total']) ?></b></td><td><a class="text-link" href="tampilan.php?id=<?= $t['id_transaksi'] ?>">Lihat struk ↗</a></td></tr><?php endwhile; else: ?><tr><td colspan="5" class="empty">Belum ada transaksi.</td></tr><?php endif; ?></tbody></table></div></section>
<?php endif; ?>
<footer>© <?= date('Y') ?> <b>ayyinns coffee</b> · Discover Your Style <span>Made with ♥ and coffee</span></footer>
</main>
</body></html>
