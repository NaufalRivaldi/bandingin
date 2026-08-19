<?php

declare(strict_types=1);

namespace App\Enums;

enum ReportType: string
{
    case Harga = 'harga';
    case Hp = 'hp';
    case Spesifikasi = 'spesifikasi';

    public function tabLabel(): string
    {
        return match ($this) {
            self::Harga => 'Lapor harga salah',
            self::Hp => 'Usul HP baru',
            self::Spesifikasi => 'Koreksi spesifikasi',
        };
    }

    public function formTitle(): string
    {
        return match ($this) {
            self::Harga => 'Lapor harga salah',
            self::Hp => 'Usulkan HP baru',
            self::Spesifikasi => 'Koreksi spesifikasi',
        };
    }

    public function formNote(): string
    {
        return match ($this) {
            self::Harga => 'Sertakan tautan produk supaya kami bisa memverifikasi dan memperbaikinya dalam 24 jam.',
            self::Hp => 'HP yang dijual resmi di Indonesia biasanya kami tambahkan dalam satu minggu.',
            self::Spesifikasi => 'Sebutkan baris mana yang keliru dan sumber resminya kalau ada.',
        };
    }

    public function subjectLabel(): string
    {
        return match ($this) {
            self::Harga => 'HP yang harganya salah',
            self::Hp => 'Nama HP dan varian',
            self::Spesifikasi => 'HP yang datanya keliru',
        };
    }

    public function subjectPlaceholder(): string
    {
        return match ($this) {
            self::Harga => 'Redmi Note 14 Pro 5G',
            self::Hp => 'POCO F7 Pro 12/256 GB',
            self::Spesifikasi => 'Galaxy A56 5G',
        };
    }

    public function notePlaceholder(): string
    {
        return match ($this) {
            self::Harga => 'Contoh: harga di Shopee sudah turun sejak kemarin sore.',
            self::Hp => 'Contoh: sudah dijual resmi sejak minggu lalu, ada di toko resmi Tokopedia.',
            self::Spesifikasi => 'Contoh: kecerahan puncak seharusnya 1.900 nits, bukan 1.200 nits.',
        };
    }

    public function confirmation(): string
    {
        return match ($this) {
            self::Harga => 'Kami cek tautannya dan memperbarui harga dalam 24 jam. Kamu akan dapat email begitu datanya diperbaiki.',
            self::Hp => 'Usulanmu masuk antrean. Kami kabari lewat email begitu HP-nya tayang di katalog.',
            self::Spesifikasi => 'Redaksi meninjau koreksi dalam dua hari kerja dan mencatat perubahannya dengan tanggal.',
        };
    }

    public function requiresStoreDetails(): bool
    {
        return $this === self::Harga;
    }
}
