<?php
declare(strict_types=1);

namespace App\Realms\Ui\Forms;

use App\Realms\Actions\DeleteRealm;
use App\Realms\Models\Realm;
use App\Realms\Ui\Concerns\ActivatesRealmContext;
use App\Shared\Concerns\ResolvesCurrentUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Lattice\Facades\Effects;
use Lattice\Form\Attributes\AsForm;
use Lattice\Form\Components\Form as FormComponent;
use Lattice\Form\Components\TextInput;
use Lattice\Form\FormDefinition;
use Lattice\Http\LatticeResponse;
use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\Emphasis;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsForm('realms.delete', can: 'delete', on: 'realm')]
final class DeleteRealmForm extends FormDefinition
{
    use ActivatesRealmContext;
    use ResolvesCurrentUser;

    public function __construct(private readonly DeleteRealm $deleteRealm) {}

    public function definition(FormComponent $form, Request $request): FormComponent
    {
        /** @var Realm $realm */
        $realm = $this->contextModel('realm');

        return $form
            ->method(HttpMethod::Delete)
            ->schema([
                Text::make(__('realm.delete.warning')),
                TextInput::make('name', __('realm.delete.confirm-name'))
                    ->placeholder($realm->name)
                    ->required()
                    ->rules(['string'])
                    ->rules(fn (): array => [function (string $attribute, mixed $value, Closure $fail) use ($realm): void {
                        if ((string) $value !== $realm->name) {
                            $fail(__('realm.delete.name-mismatch'));
                        }
                    }]),
            ])
            ->submitLabel(__('realm.delete.submit'))
            ->submitEmphasis(Emphasis::Outline);
    }

    public function handle(Request $request): LatticeResponse
    {
        /** @var Realm $realm */
        $realm = $this->contextModel('realm');

        // The realm's users go with it, including whoever submitted this — so
        // the session is done the moment the transaction commits.
        $this->deleteRealm->handle($realm);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Effects::respond()->toast(__('realm.delete.deleted'), Variant::Success)
            ->toRoute('home');
    }
}
