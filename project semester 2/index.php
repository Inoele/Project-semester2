<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Pecel Lele</title>

<style>
body {
    font-family: 'Segoe UI', Arial;
    background: linear-gradient(135deg, #ff4d4d, #ff9999);
    padding: 20px;
}

.container {
    background: white;
    padding: 25px;
    border-radius: 20px;
    max-width: 650px;
    margin: auto;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

h2 {
    text-align: center;
    color: #cc0000;
}

form {
    display: flex;
    flex-direction: column;
    gap: 12px;
    align-items: center;
}

input, select {
    padding: 10px;
    border-radius: 10px;
    border: 1px solid #ddd;
    width: 95%;
    font-size: 14px;
}

button {
    padding: 12px;
    background: linear-gradient(135deg, #ff1a1a, #cc0000);
    border: none;
    color: white;
    border-radius: 10px;
    width: 100%;
    font-weight: bold;
    cursor: pointer;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    font-size: 14px;
}

th {
    background: #cc0000;
    color: white;
    padding: 10px;
}

td {
    padding: 10px;
    border-bottom: 1px solid #eee;
    text-align: center;
}

tr:nth-child(even) {
    background: #fff5f5;
}

td button {
    background: #ff0000;
    border-radius: 50%;
    padding: 5px 8px;
}

.masuk { color: green; font-weight: bold; }
.keluar { color: red; font-weight: bold; }

h3 {
    margin-top: 10px;
    color: #b30000;
    text-align: center;
}
</style>
</head>

<body>

<div class="container">
<h2>🌶️ Laporan Pemasukan & Pengeluaran</h2>

<form method="POST" id="formInput" action="">

<select name="jenis" id="jenis" onchange="ubahForm()" required>
    <option value="masuk">Pemasukan</option>
    <option value="keluar">Pengeluaran</option>
</select>

<input type="date" name="tanggal" required>

<div id="formMasuk">
<select id="menu" onchange="isiHarga()">
    <option value="">Pilih Menu</option>
    <option value="ayam goreng">Ayam Goreng</option>
    <option value="lele goreng">Lele Goreng</option>
    <option value="ikan gurame goreng">Ikan Gurame</option>
    <option value="bandeng presto">Bandeng Presto</option>
    <option value="nasi">Nasi</option>
    <option value="lalapan">Lalapan</option>
    <option value="sambel">Sambel</option>
</select>

<input type="number" id="hargaMasuk" readonly>
<input type="number" id="qty" placeholder="Jumlah">
<input type="text" name="keterangan" placeholder="Keterangan">
</div>

<div id="formKeluar" style="display:none;">
<input type="text" name="belanja" placeholder="Belanja apa?">
<input type="number" id="hargaKeluar" placeholder="Harga">
<input type="text" name="keterangan_keluar" placeholder="Keterangan">
</div>

<div id="keranjang"></div>
<button type="button" onclick="tambahItem()">+ Tambah Item</button>

<input type="hidden" name="jumlah" id="jumlah">
<input type="hidden" name="detail" id="detail">

<button type="submit" name="submit">Selesai</button>
</form>

<form method="GET" action="">
    <input type="month" name="bulan" value="<?php echo isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m'); ?>">
    <button type="submit">Filter Bulan</button>
</form>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$file = __DIR__ . "/data.json";

/* pastikan file ada */
if (!file_exists($file)) {
    file_put_contents($file, json_encode([]), LOCK_EX);
}

$data = json_decode(file_get_contents($file), true);
if (!$data) $data = [];

$bulanDipilih = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');

/* hapus */
if (isset($_POST['hapus'])) {
    unset($data[$_POST['hapus']]);
    $data = array_values($data);

    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
    $bulanBaru = substr($_POST['tanggal'], 0, 7);
header("Location: index.php?bulan=".$bulanBaru);
    exit;
}

/* simpan */
if (isset($_POST['submit'])) {

    $ket = ($_POST['jenis']=="masuk")
        ? $_POST['keterangan']
        : $_POST['belanja']." | ".$_POST['keterangan_keluar'];

    $data[] = [
        "tanggal" => $_POST['tanggal'],
        "jenis" => $_POST['jenis'],
        "keterangan" => $ket." | ".($_POST['detail'] ?? ''),
        "jumlah" => (int)$_POST['jumlah']
    ];

    file_put_contents($file,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );

    header("Location: index.php?bulan=".$bulanDipilih);
    exit;
}

/* tampil */
function rupiah($a){ return "Rp ".number_format($a,0,',','.'); }

$totalMasuk=0; $totalKeluar=0;

echo "<table><tr><th>Tanggal</th><th>Jenis</th><th>Keterangan</th><th>Jumlah</th><th>Aksi</th></tr>";

foreach($data as $i=>$d){
    if(substr($d['tanggal'],0,7)!=$bulanDipilih) continue;

    if($d['jenis']=="masuk"){ $totalMasuk+=$d['jumlah']; $c="masuk"; }
    else { $totalKeluar+=$d['jumlah']; $c="keluar"; }

    echo "<tr>
    <td>{$d['tanggal']}</td>
    <td class='$c'>{$d['jenis']}</td>
    <td>{$d['keterangan']}</td>
    <td>".rupiah($d['jumlah'])."</td>
    <td>
    <form method='POST'>
    <input type='hidden' name='hapus' value='$i'>
    <button>❌</button>
    </form>
    </td></tr>";
}

$saldo=$totalMasuk-$totalKeluar;

echo "</table>";
echo "<h3>Total Pemasukan: ".rupiah($totalMasuk)."</h3>";
echo "<h3>Total Pengeluaran: ".rupiah($totalKeluar)."</h3>";
echo "<h3>Saldo: ".rupiah($saldo)."</h3>";
?>

</div>

<script>
let list=[];

function formatRupiah(a){ return "Rp "+Number(a).toLocaleString("id-ID"); }

function ubahForm(){
let j=jenis.value;
formMasuk.style.display=(j=="masuk")?"block":"none";
formKeluar.style.display=(j=="keluar")?"block":"none";
}

function isiHarga(){
let h={
"ayam goreng":10000,"lele goreng":5000,"ikan gurame goreng":10000,
"bandeng presto":12000,"nasi":5000,"lalapan":5000,"sambel":5000
};
hargaMasuk.value=h[menu.value]||0;
}

function tambahItem(){
let j=jenis.value, item={};

if(j=="masuk"){
if(!menu.value||!qty.value)return alert("Isi dulu!");
item={ket:menu.value,jumlah:hargaMasuk.value*qty.value};
}else{
let b=document.querySelector("[name=belanja]").value;
let h=hargaKeluar.value;
if(!b||!h)return alert("Isi dulu!");
item={ket:b,jumlah:h};
}

list.push(item); renderList();
}

function renderList(){
keranjang.innerHTML=list.map((i,x)=>
`<div>${i.ket} - ${formatRupiah(i.jumlah)}
<button onclick="hapusItem(${x})">❌</button></div>`
).join("");
}

function hapusItem(i){ list.splice(i,1); renderList(); }

formInput.addEventListener("submit",e=>{
if(!list.length){ e.preventDefault(); return alert("Tambahkan item dulu!"); }

let t=0; list.forEach(i=>t+=parseInt(i.jumlah));
jumlah.value=t;
detail.value=JSON.stringify(list);
});
</script>

</body>
</html>