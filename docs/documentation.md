📌 Query Builder & Utilities

## Insert
```php
use Core\Classes\Chiamate;

$c = new Chiamate();

$data = [
  'nome'  => $nome,
  'email' => $email,
  'stato' => 1
];

$lastId = $c->salva('utenti', $data);

var_export($c->GetError());
```

## Select & Select with JOIN
```php
  use Core\Classes\Chiamate;

$c = new Chiamate();

$and = [
  'stato' => 1
];

$join = [
  'ruoli' => [
    'JOIN' => 'LEFT',
    'ruoli.id' => 'utenti.id_ruolo'
  ]
];

$rows = $c->seleziona(
  'utenti',
  'id',
  $idUtente,
  $and,
  $join
);

var_export($c->GetError());
```

## Update
```php
  use Core\Classes\Chiamate;

$c = new Chiamate();

$data = [
  'email' => $email,
  'stato' => 0
];

$ok = $c->aggiorna(
  'utenti',
  $data,
  'id',
  $idUtente
);

var_export($c->GetError());
```

## Delete
```php
use Core\Classes\Chiamate;

$c = new Chiamate();

$affected = $c->Cancella(
  'utenti',
  'id',
  $idUtente
);

var_export($c->GetError());
```

## Duplicate check
```php
  use Core\Classes\Chiamate;

$c = new Chiamate();

$found = $c->DoppiDinamica(
  'utenti',
  'email',
  $email
);

var_export($c->GetError());
```

## Crypter & Descrypter
```php
  use Core\Classes\Crypter;

$secret = 'password-segreta';

$crypted = Crypter::encrypt(
  'Marco Dattisi',
  $secret
);

$clear = Crypter::decrypt(
  $crypted,
  $secret
);
```

## File upload
```php
  use MarcoUpload\MarcoUpload;

$upload = new MarcoUpload($_SERVER['DOCUMENT_ROOT']);

$path = $upload->upload($_FILES['file'], [
  'move' => '/uploads/',
  'size' => 2000000,
  'type' => ['jpg','png']
]);

if (!$upload->getErros()) {
  echo $path;
} else {
  var_export($upload->getErros());
```

## AJAX helpers
```js
AjaxHelper.post("/private/endpoint", {
  id: 5,
  nome: "Mario"
}).then(resp => {
  console.log(resp);
});
```

## AJAX Response from Controllers
```php

return $this->jsonResponse(true, "OK", [
  "redirect" => "/private/dashboard"
]);

```