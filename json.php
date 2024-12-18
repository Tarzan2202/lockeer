<?php
include('class_conn.php');
$cls_conn = new class_conn;

$sql = "SELECT * from tb_locker";
$rs = $cls_conn->select_base($sql);
while ($row = mysqli_fetch_array($rs)) {
	$data['locker1'] = $row['locker1'];
	$data['locker2'] = $row['locker2'];
}

echo json_encode($data);
?>