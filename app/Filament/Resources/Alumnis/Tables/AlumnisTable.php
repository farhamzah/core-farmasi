<?php

namespace App\Filament\Resources\Alumnis\Tables;

use App\Models\Alumni;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AlumnisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student_number')->label('NIM')->searchable()->sortable(),
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('studyProgram.name')
                    ->label('Program Studi')
                    ->placeholder(fn ($record): string => $record->program_name_snapshot ?: '-'),
                TextColumn::make('graduation_year')->label('Lulus')->sortable()->placeholder('-'),
                TextColumn::make('personal_email')->label('Email')->searchable()->toggleable(),
                TextColumn::make('whatsapp')->label('WhatsApp')->searchable()->toggleable(),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
                TextColumn::make('source')->label('Sumber')->badge()->toggleable(),
                TextColumn::make('user.email')->label('Akun Core')->placeholder('-')->toggleable(),
                BooleanColumn::make('active')->label('Aktif'),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('study_program_id')
                    ->label('Program Studi')
                    ->relationship('studyProgram', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('graduation_year')
                    ->label('Tahun lulus')
                    ->options(fn (): array => Alumni::query()
                        ->whereNotNull('graduation_year')
                        ->distinct()
                        ->orderByDesc('graduation_year')
                        ->pluck('graduation_year', 'graduation_year')
                        ->mapWithKeys(fn ($year): array => [(string) $year => (string) $year])
                        ->all()),
                SelectFilter::make('status')->options([
                    'verified' => 'Terverifikasi',
                    'pending' => 'Menunggu verifikasi',
                    'inactive' => 'Tidak aktif',
                ]),
                TernaryFilter::make('active')->label('Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
