$.fn.dataTable.ext.type.order['grades-pre'] = function ( d ) {
    switch ( d ) {
        case 'KG1':     return 1;
        case 'KG2':     return 2;
        case 'KG3':     return 3;
        case 'PK3':     return 1;
        case 'PK4':     return 2;
        case 'KG':      return 3;
        case 'G1':      return 4;
        case 'G2':      return 5;
        case 'G3':      return 6;
        case 'G4':      return 7;
        case 'G5':      return 8;
        case 'G6':      return 9;
        case 'G7':      return 10;
        case 'G8':      return 11;
        case 'G9':      return 12;
        case 'G10':     return 13;
        case 'G11':     return 14;
        case 'G12':     return 15;
    }
    return 0;
};
