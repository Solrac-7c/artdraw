# ArtDraw - Sprint 1

Estrutura de pastas, layout compartilhado e CSS base, seguindo o padrão do
projeto de referência (rot-vale-main) e o layout do Telas.pdf.

## Estrutura

```
www/
├── index.php              Tela principal (pública)
├── assets/
│   ├── css/style.css
│   └── img/
├── includes/
│   ├── header.php         abre HTML, carrega CSS, abre <main>
│   ├── menu.php           barra superior: logo | pesquisa | perfil
│   ├── busca.php          barra de pesquisa + painel Filtragem
│   ├── card_aula.php      card reutilizável de aula
│   ├── footer.php         fecha <main> e HTML
│   ├── auth.php           exige login (ligado no Sprint 4)
│   └── autoriza.php       exige papel instrutor/adm (ligado no Sprint 4)
├── login/       index.php
├── cadastro/    index.php
├── perfil/      index.php, editar.php
├── salvas/      mostrar.php
└── aulas/       ver.php, index.php, mostrar.php, editar.php
```

Pastas e arquivos que ainda não existem (config/, arquivos processa.php,
salvar.php, autenticar.php, excluir.php, logout.php, categorias/) entram nos
sprints seguintes.

## Convenções

- `www/` é a raiz do servidor: links e CSS usam caminhos absolutos
  (`/assets/css/style.css`), como no projeto de referência.
- Toda página define `$tituloPagina` e inclui `header.php` e `footer.php`.
- A tela principal define `$mostrarBusca = true` para exibir a pesquisa.
- Mensagens de feedback usam `$_SESSION["msg"]` e `$_SESSION["class"]`
  (`alert alert-success`, `alert alert-danger`, `alert alert-warning`).

## Como visualizar (sem Docker)

Com PHP instalado, na pasta do projeto:

```
php -S localhost:8080 -t www
```

Abra http://localhost:8080. O Docker entra no Sprint 3.
