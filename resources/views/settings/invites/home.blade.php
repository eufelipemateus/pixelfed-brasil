@extends('settings.template')

@section('section')

<div class="mb-4">
  <h3 class="font-weight-bold">Convites</h3>
  <p class="text-muted">Compartilhe seu link único com amigos e veja quem se registrou com ele.</p>
</div>

<hr>

<div class="mb-4">
  <p><strong>Seu código de indicação:</strong> {{ auth()->user()->refer_code }}</p>
  <p>
    <strong>Seu link de convite:</strong>
    <input type="text" readonly class="form-control w-100"
           value="{{ route('register', ['ref' => auth()->user()->refer_code]) }}">
  </p>
</div>

@if($referrals->count() > 0)
  <h5 class="mt-5">Contas que usaram seu código:</h5>
  <div class="table-responsive">
    <table class="table table-striped table-sm mt-3">
      <thead>
        <tr>
          <th>#</th>
          <th>Usuario</th>
          <th>Data de Cadastro</th>
        </tr>
      </thead>
    <tbody>
      @foreach($referrals as $user)
        <tr>
        <td>{{ $user->id }}</td>
        <td>
          <a href="{{ route('web.profile', ['id' => $user->profile_id]) }}">
            {{ $user->username }}
          </a>
        </td>
        <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
        </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <td colspan="3">
            {{ $referrals->links() }}

        </td>
      </tr>
    </tfoot>
    </table>
  </div>
@else
  <div class="text-center py-5">
    <i class="fas fa-user-friends text-muted fa-4x mb-3"></i>
    <h5>Ninguém se cadastrou com seu código ainda.</h5>
    <p class="text-muted">Compartilhe seu link acima e acompanhe aqui os cadastros.</p>
  </div>
@endif

@endsection
