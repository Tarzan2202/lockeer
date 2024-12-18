<?php
date_default_timezone_set("Asia/Bangkok");
class class_conn
{

	//ตั้งฐานข้อมูล
	public $db_server = "localhost";
	public $db_username = "root";
	public $db_password = "123456789";
	public $db_database = "locker";
	//ฟังก์ชั่นในการเรียกดูข้อมูล จะใช้กับ select ต่างๆ

	public function select_base($sql)
	{
		$db_server = $this->db_server; //เรียกตัวแปร db_server จาก public มาใส่ใน $db_server ในฟังก์ชั่น
		$db_username = $this->db_username;
		$db_password = $this->db_password;
		$db_database = $this->db_database;
		$con = mysqli_connect($db_server, $db_username, $db_password, $db_database);
		mysqli_set_charset($con, "utf8"); //set ข้อมูลในฐานข้อมูลเป็นภาษาไทย
		if (mysqli_connect_errno()) {
			echo "Failed to connect to MySQL: " . mysqli_connect_error();
		}
		$result = mysqli_query($con, $sql);
		return $result;
		mysqli_close($con);
	}

	//ฟังก์ชั่นในการเพิ่ม/ลบ/แก้ไขข้อมูลลงฐานข้อมูลใช้กับคำสั่ง insert,delete,update
	public function write_base($sql)
	{
		$db_server = $this->db_server; //เรียกตัวแปร db_server จาก public มาใส่ใน $db_server ในฟังก์ชั่น
		$db_username = $this->db_username;
		$db_password = $this->db_password;
		$db_database = $this->db_database;
		$con = mysqli_connect($db_server, $db_username, $db_password, $db_database);
		mysqli_set_charset($con, "utf8"); //set ข้อมูลในฐานข้อมูลเป็นภาษาไทย
		if (mysqli_connect_errno()) {

			return false;
		} else {
			mysqli_query($con, $sql);
			mysqli_close($con);
			return true;
		}
	}
	//ฟังก์ชั่นในการนับจำนวนแถว
	public function select_numrows($sql)
	{
		$db_server = $this->db_server; //เรียกตัวแปร db_server จาก public มาใส่ใน $db_server ในฟังก์ชั่น
		$db_username = $this->db_username;
		$db_password = $this->db_password;
		$db_database = $this->db_database;
		$con = mysqli_connect($db_server, $db_username, $db_password, $db_database);
		mysqli_set_charset($con, "utf8"); //set ข้อมูลในฐานข้อมูลเป็นภาษาไทย
		if (mysqli_connect_errno()) {
			echo "Failed to connect to MySQL: " . mysqli_connect_error();
		}
		$result = mysqli_query($con, $sql);
		$rowcount = mysqli_num_rows($result);
		return $rowcount;
		mysqli_close($con);
	}
	//ฟังก์ชั่นแสดงข้อความ
	public function show_message($word)
	{
		return "<script type='text/javascript'>alert('$word');</script>";
	}
	//ฟังก์ชั่นในการลิงค์ไปหน้าอื่น
	public function goto_page($speed, $url)
	{
		return "<meta http-equiv='refresh' content='$speed;$url' />";
	}
}
