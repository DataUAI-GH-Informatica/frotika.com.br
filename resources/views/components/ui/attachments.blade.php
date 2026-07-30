@props([
    'attachments',
    'type',
    'ownerId',
    'canManage' => false,
    'emptyHint' => 'Guarde aqui a nota, o cupom ou a foto que comprova este lançamento.',
])

@php
    use App\Domain\Attachments\Support\AttachmentRules;

    $uploadErrors = collect($errors->getMessages())
        ->filter(fn ($messages, $key) => $key === 'attachments' || str_starts_with($key, 'attachments.'))
        ->flatten()
        ->unique()
        ->values();
@endphp

<div class="rounded-lg border border-slate-200 bg-white">
    <div class="flex items-center justify-between border-b border-slate-200 px-4 py-2.5">
        <h2 class="text-sm font-semibold text-slate-900">Anexos</h2>
        @if ($attachments->isNotEmpty())
            <span class="font-mono text-2xs tabular text-slate-400">{{ $attachments->count() }}</span>
        @endif
    </div>

    @if ($attachments->isEmpty())
        <p class="px-4 py-3 text-sm text-slate-500">{{ $emptyHint }}</p>
    @else
        <ul>
            @foreach ($attachments as $attachment)
                <li class="flex h-11 items-center gap-3 border-b border-slate-100 px-4 sm:h-9">
                    <a href="{{ route('attachments.download', ['attachment' => $attachment->getKey()]) }}"
                        title="{{ $attachment->original_name }} · enviado por {{ $attachment->uploader?->name ?? 'usuário removido' }} em {{ Format::dateTime($attachment->created_at) }}"
                        class="min-w-0 flex-1 truncate text-sm text-slate-900 hover:text-brand-700 hover:underline">{{ $attachment->original_name }}</a>

                    <span class="shrink-0 font-mono text-2xs tabular text-slate-400">
                        {{ $attachment->extension() }} · {{ Format::fileSize($attachment->size_bytes) }} · {{ Format::dayMonth($attachment->created_at) }}
                    </span>

                    @if ($canManage)
                        <form method="POST" action="{{ route('attachments.destroy', ['attachment' => $attachment->getKey()]) }}"
                            onsubmit="return confirm('Excluir {{ $attachment->original_name }}? O arquivo é apagado de vez.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Excluir anexo"
                                class="flex size-11 shrink-0 items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-slate-100 hover:text-danger-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/30 sm:size-6">
                                <span aria-hidden="true">&times;</span>
                                <span class="sr-only">Excluir</span>
                            </button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if ($canManage)
        <form method="POST" enctype="multipart/form-data"
            action="{{ route('attachments.store', ['owner' => $type->slug(), 'id' => $ownerId]) }}"
            class="px-4 py-3">
            @csrf

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <input type="file" name="attachments[]" multiple required
                    accept="{{ collect(AttachmentRules::allowedExtensions())->map(fn ($ext) => '.' . $ext)->join(',') }}"
                    class="min-w-0 flex-1 text-base text-slate-600 file:mr-3 file:h-9 file:cursor-pointer file:rounded-md file:border file:border-slate-300 file:bg-white file:px-3 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-50 sm:text-sm" />

                <x-ui.button type="submit" variant="secondary" class="w-full sm:w-auto">Anexar</x-ui.button>
            </div>

            <p class="mt-2 text-xs text-slate-500">
                {{ AttachmentRules::humanExtensions() }} · até {{ AttachmentRules::humanMaxSize() }} por arquivo.
            </p>

            @foreach ($uploadErrors as $message)
                <p class="mt-1 text-sm text-danger-700">{{ $message }}</p>
            @endforeach
        </form>
    @endif
</div>
