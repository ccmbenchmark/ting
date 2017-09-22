<?php
$conn = oci_connect(
    'system',
    'oracle',
    'localhost/XE'
);

/*
$sql = 'CREATE TABLE PLAYERS (ID INT)';
$stid = oci_parse($conn, $sql);
oci_execute($stid);die;
*/

/*$sql = 'CREATE SEQUENCE players_seq START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE';
$stid = oci_parse($conn, $sql);
oci_execute($stid);die;*/

$sql = 'INSERT INTO PLAYERS VALUES(players_seq.nextval)';
$stid = oci_parse($conn, $sql);
oci_execute($stid);

//var_dump(oci_statement_type($stid));


/*$sql = 'SELECT * FROM PLAYERS ORDER BY ID ASC';
$stid = oci_parse($conn, $sql);
oci_execute($stid);
//while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
while ($row = oci_fetch_object($stid)) {
    var_dump($row->ID);die;
}

echo "\n" . 'Affected rows: ' . oci_num_rows($conn);*/

/*$sql = 'SELECT players_seq.currval FROM dual';
$stid = oci_parse($conn, $sql);
oci_execute($stid);

$row = oci_fetch_assoc($stid);
var_dump($row['CURRVAL']);die;*/

while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
    echo "CURRVAL IS: " . $row['CURRVAL'];
}

die;
$sql = 'SELECT * FROM DEMO_STATES';
$stid = oci_parse($conn, $sql);
oci_execute($stid);
while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
    echo "\n" . $row['STATE_NAME'];
}