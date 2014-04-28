<?php

$datasource_guid = (int) get_input("datasource_guid");

echo $datasource_guid;

$ps = new ProfileSyncMySQL();

