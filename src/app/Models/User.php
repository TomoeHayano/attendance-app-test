<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class User extends Authenticatable implements MustVerifyEmail
{
  use Notifiable;

  /** @var array<string> */
  protected $fillable = [
    'name',
    'email',
    'password',
  ];

  /** @var array<string> */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /** @var array<string, string> */
  protected $casts = [
    'email_verified_at' => 'datetime',
  ];
}
