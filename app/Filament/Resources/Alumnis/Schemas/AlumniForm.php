<?php

namespace App\Filament\Resources\Alumnis\Schemas;

use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AlumniForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Alumni')
                    ->schema([
                        Forms\Components\TextInput::make('student_number')
                            ->label('NIM')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama lengkap')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('personal_email')
                            ->label('Email pribadi')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->tel()
                            ->maxLength(50),
                    ])
                    ->columns(2),
                Section::make('Riwayat Akademik')
                    ->schema([
                        Forms\Components\Select::make('study_program_id')
                            ->label('Program Studi')
                            ->relationship('studyProgram', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('program_name_snapshot')
                            ->label('Nama prodi saat lulus')
                            ->helperText('Dipakai bila program studi belum tersedia atau namanya berubah.')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('entry_year')
                            ->label('Tahun masuk')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(2200),
                        Forms\Components\TextInput::make('graduation_year')
                            ->label('Tahun lulus')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(2200),
                        Forms\Components\DatePicker::make('graduation_date')
                            ->label('Tanggal lulus'),
                    ])
                    ->columns(2),
                Section::make('Relasi Core')
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->label('Data mahasiswa asal')
                            ->relationship('student', 'student_number')
                            ->searchable(['student_number', 'name'])
                            ->preload()
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('user_id')
                            ->label('Akun Core')
                            ->relationship('user', 'email')
                            ->searchable(['name', 'email', 'username'])
                            ->preload()
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('source')
                            ->label('Sumber data')
                            ->options([
                                'manual' => 'Input manual Core',
                                'student_conversion' => 'Konversi mahasiswa',
                                'karir_approval' => 'Persetujuan pendaftaran Alumni Farmasi',
                                'import' => 'Import terkontrol',
                            ])
                            ->default('manual')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'verified' => 'Terverifikasi',
                                'pending' => 'Menunggu verifikasi',
                                'inactive' => 'Tidak aktif',
                            ])
                            ->default('verified')
                            ->required(),
                        Forms\Components\Toggle::make('active')
                            ->label('Aktif')
                            ->default(true),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan internal')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
