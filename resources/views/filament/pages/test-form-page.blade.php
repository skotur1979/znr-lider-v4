<x-filament-panels::page>

<style>

.test-card{
    background:#ffffff;
    border:1px solid #d1d5db;
    border-radius:12px;
    padding:20px;
}

.dark .test-card{
    background:#1f2937;
    border-color:#374151;
}

.answer-option{
    display:flex;
    gap:12px;
    align-items:center;
    border:1px solid #d1d5db;
    border-radius:10px;
    padding:12px;
    cursor:pointer;
    margin-bottom:10px;
    transition:all .15s;
}

.dark .answer-option{
    border-color:#374151;
}

.answer-option:hover{
    border-color:#f97316;
}

.answer-option input{
    width:22px;
    height:22px;
    accent-color:#f97316;
}

.answer-option input:checked + span{
    font-weight:600;
}

.answer-option:has(input:checked){
    border-color:#f97316;
    background:#fff7ed;
}

.dark .answer-option:has(input:checked){
    background:#2a1a0a;
}

.answer-option span{
    font-size:16px;
}

.question-number{
    width:36px;
    height:36px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#e5e7eb;
    font-weight:700;
    font-size:15px;
}

.dark .question-number{
    background:#374151;
}

.question-text{
    font-size:20px;
    font-weight:700;
    line-height:1.4;
}

</style>


<div style="max-width:1100px;margin:auto">

<div style="
display:flex;
align-items:center;
gap:12px;
margin-bottom:22px;
padding-bottom:10px;
border-bottom:1px solid rgba(255,255,255,.1);
">

<div style="
width:40px;
height:40px;
border-radius:50%;
background:#1e3a8a;
color:white;
display:flex;
align-items:center;
justify-content:center;
font-size:20px;
">
📝
</div>

<div>

<div style="font-size:30px;font-weight:800;line-height:1.2">
{{ $this->test->naziv }}
</div>

@if(!empty($this->test->minimalni_prolaz))
<div style="font-size:15px;color:#f97316;font-weight:600;margin-top:2px">
Minimalni prolaz: {{ $this->test->minimalni_prolaz }}%
</div>
@endif

</div>

</div>


@if (! $this->submitted)

{{-- PODACI KANDIDATA --}}
<div class="test-card" style="margin-bottom:20px">

<div style="font-weight:600;margin-bottom:10px">
Podaci kandidata
</div>

<div style="display:flex;gap:20px;flex-wrap:wrap">

<div style="flex:1;min-width:200px">
<label>Ime i prezime</label>
<input type="text"
wire:model="ime_prezime"
style="width:100%;padding:8px;border-radius:8px;border:1px solid #ccc">
</div>

<div style="flex:1;min-width:200px">
<label>Radno mjesto</label>
<input type="text"
wire:model="radno_mjesto"
style="width:100%;padding:8px;border-radius:8px;border:1px solid #ccc">
</div>

<div style="flex:1;min-width:200px">
<label>Datum rođenja</label>
<input type="date"
wire:model="datum_rodjenja"
style="width:100%;padding:8px;border-radius:8px;border:1px solid #ccc">
</div>

</div>
</div>


{{-- PITANJA --}}
@foreach ($this->test->questions as $question)

<div class="test-card" style="margin-bottom:18px">

<div style="display:flex;gap:12px;margin-bottom:14px;align-items:center">

<div class="question-number">
{{ $loop->iteration }}
</div>

<div class="question-text">
{{ $question->tekst }}

@if ($question->visestruki_odgovori)
<span style="color:#f97316;font-size:14px;margin-left:6px">
(više točnih odgovora)
</span>
@endif

</div>

</div>


@if ($question->slika_path)

<img
src="{{ Storage::url($question->slika_path) }}"
style="max-width:300px;margin-bottom:12px;border-radius:8px">

@endif


@foreach ($question->answers as $answer)

@php
$inputId = 'q'.$question->id.'_a'.$answer->id;
@endphp


<label class="answer-option" for="{{ $inputId }}">

@if ($question->visestruki_odgovori)

<input
id="{{ $inputId }}"
type="checkbox"
wire:model.defer="odgovori.{{ $question->id }}.{{ $answer->id }}">

@else

<input
id="{{ $inputId }}"
type="radio"
value="{{ $answer->id }}"
wire:model.defer="odgovori.{{ $question->id }}">

@endif


<span>

@if ($answer->slika_path)

<img
src="{{ Storage::url($answer->slika_path) }}"
style="width:120px;height:120px;object-fit:contain;border-radius:6px;margin-bottom:6px">

@endif

{{ $answer->tekst }}

</span>

</label>

@endforeach

</div>

@endforeach


<div style="display:flex;gap:10px;justify-content:flex-end">

<x-filament::button
tag="a"
color="gray"
href="{{ \App\Filament\Pages\AvailableTestsPage::getUrl() }}"
>
← Povratak
</x-filament::button>


<x-filament::button type="submit">
Pošalji test
</x-filament::button>

</div>


@else


{{-- REZULTAT --}}
<div class="test-card">

<div style="text-align:center">

<h2 style="font-size:24px;font-weight:700">
Rezultat: {{ round($this->rezultat,2) }}%
</h2>

@if ($this->prolaz)

<div style="color:#16a34a;font-weight:600">
Test je položen
</div>

@else

<div style="color:#dc2626;font-weight:600">
Test nije položen
</div>

@endif

</div>

</div>

@endif

</div>

</x-filament-panels::page>