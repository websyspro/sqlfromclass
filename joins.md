BoxEntity
  |-- Operador (OneToOne)
  |-- DocumentEntity (OneToMany)
        |-- BoxEntity (OneToOne)
        |-- Operador (OneToOne)
        |-- CustomerEntity (OneToOne)
        |-- DocumentItemEntity (OneToMany)
              |-- ProductEntity (OneToOne)
                    |-- ProductGroupEntity (OneToOne)