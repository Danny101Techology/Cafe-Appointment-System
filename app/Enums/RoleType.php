<?php
namespace App\Enums;

enum RoleType: int
{
    case Admin = 1;
    case Manager = 2;
    case User = 3;
}

?>
