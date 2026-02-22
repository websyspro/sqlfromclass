<?php

namespace Websyspro\SqlFromClass;

use Websyspro\Commons\Collection;
use Websyspro\Commons\Util;

/**
 * Classe responsável por processar e analisar declarações de use
 * 
 * Recebe uma string contendo uma declaração de use (ex: "use App\\Models\\User;")
 * e extrai o nome da classe e o caminho do namespace para uso posterior
 */
class UseClass
{
  /** @var string Nome da classe extraída do namespace */
  public string $class;
  
  /** @var string Caminho do namespace sem o nome da classe */
  public string $path;

  /**
   * @param string $useClass String contendo a declaração de use (ex: "use App\\Models\\User;")
   */
  public function __construct(
    private string $useClass
  ){
    $this->defineUseClass();
  }

  /**
   * Verifica se o nome da classe extraída corresponde ao nome fornecido
   * 
   * @param string $class Nome da classe para comparação
   * @return bool True se os nomes coincidirem, false caso contrário
   */
  public function isClass(
    string $class
  ): bool {
    return $this->class === $class;
  }

  /**
   * Retorna o namespace completo de um enum
   * 
   * @param string $unitEnum Nome do case do enum
   * @return string Namespace completo no formato "Path\\ClassName::CaseName"
   */
  public function fullClassFromUnitEnum(
    string $unitEnum
  ): string {
    return Util::sprintFormat(
      "%s\%s::%s", [
        $this->path, $this->class, $unitEnum
      ]
    );
  }

  /**
   * Processa a string de use para extrair o nome da classe e o caminho do namespace
   */
  private function defineUseClass(
  ): void {
    // Remove caracteres de formatação e quebras de linha da string use
    // e divide o namespace em partes usando a barra invertida como separador
    $useList = new Collection(
      preg_split( 
        "#\\\#",  // Divide pela barra invertida (\)
        preg_replace(
          [ 
            "#\r#",        // Remove carriage return
            "#\n\s*#",     // Remove quebras de linha e espaços
            "#use\s+#",    // Remove a palavra "use" e espaços
            "#;$#"         // Remove ponto e vírgula no final
          ],
          "",
          $this->useClass
        ),
        -1,
        PREG_SPLIT_NO_EMPTY  // Ignora elementos vazios
      )
    );

    // O nome da classe é o último elemento do namespace
    $this->class = $useList->last();
    
    // O caminho é formado por todos os elementos exceto o último,
    // reunidos novamente com barras invertidas
    $this->path = $useList->slice(
      0, -1
    )->join( "\\" );
  }
}