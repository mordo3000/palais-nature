<div>
    <x-slot name="title">Espace membre</x-slot>
    @include('layouts.header')

    @include('livewire.checkout.address.create')
    @include('livewire.checkout.address.delete')
    @include('livewire.profil.invoice.show')


    <div class="container pb-5 mb-2 mb-md-3">
        <div class="row">
            @include('livewire.profil.account')
            <!-- Content  -->
            <div class="mt-5 col-lg-8 order-lg-1 order-0">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-warning">
                            <div class="card-header border-warning">
                                Informations personnelles
                                <span class="float-right">
                                    <a href="{{ route('profile.show') }}"><i class="icofont-pen-alt-1"></i></a>
                                </span>
                            </div>
                            <div class="card-body">
                                <p class="card-text font-size-sm">
                                    <i class="icofont-user-alt-2"></i>
                                    {{ auth()->user()->first_name.' '.auth()->user()->name }}
                                </p>
                                <p class="card-text font-size-sm">
                                    <i class="icofont-envelope-open"></i>
                                    {{ auth()->user()->email }}
                                </p>
                                @if (auth()->user()->phone)
                                    <p class="card-text font-size-sm">
                                        <i class="icofont-phone"></i>
                                        {{ auth()->user()->phone }}
                                    </p>
                                @endif
                                <p class="card-text font-size-sm">
                                    <a href="{{ route('profile.show') }}#update-password">
                                        <i class="icofont-ui-lock"></i>
                                        Modifier mot de passe
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border-warning">
                            <div class="card-header border-warning">
                                Adresses de livraison
                                <span class="float-right">
                                    <button wire:click='openModal' data-bs-toggle="modal" data-bs-target="#address" class="p-0 btn">
                                        <i class="icofont-plus"></i>
                                    </button>
                                </span>
                            </div>
                            <div class="card-body">
                                @foreach ($addresses as $item)
                                    <p class="card-text font-size-sm">
                                        <a href="#" wire:click='addressEdit({{ $item->id }})' data-bs-toggle="modal" data-bs-target="#address">
                                            <i class="icofont-pen-alt-1"></i>
                                            {{ $item->title }}
                                        </a>
                                    </p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <header class="my-4 section-header">
                            <h3 class=>
                                Mes commandes
                            </h3>
                        </header>
                    </div>
                    <div class="col-12">
                        <div class="table-responsive font-size-md">
                            @include('livewire.profil.invoice.item')
                        </div>
                    </div>
                    <div class="btn btn-warning btn-sm">
                        <a href="{{ route('invoice.all')}}" class="text-white">Voir toutes les commandes</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('script')
        <script src="https://cdn.qenium.com/asset/libs/sticky-kit/dist/sticky-kit.min.js"></script>

        <script>
            window.livewire.on('formClose', () => {
                $('#clear, #delete, #address, #invoice').modal('hide');
            });
        </script>
    @endpush
</div>
