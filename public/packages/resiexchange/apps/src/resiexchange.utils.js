'use strict';
/**
* Converts to lower case and strips accents
* this method is used in myListFilter, a custom filter for dsiplaying categories list
* using the oi-select angular plugin
*
* note : this is not valid for non-latin charsets !
*/
String.prototype.toASCII = function () {
    var str = this.toLocaleLowerCase();
    var result = '';
    var convert = {
        192:'A', 193:'A', 194:'A', 195:'A', 196:'A', 197:'A',
        224:'a', 225:'a', 226:'a', 227:'a', 228:'a', 229:'a',
        200:'E', 201:'E', 202:'E', 203:'E',
        232:'e', 233:'e', 234:'e', 235:'e',
        204:'I', 205:'I', 206:'I', 207:'I',
        236:'i', 237:'i', 238:'i', 239:'i',
        210:'O', 211:'O', 212:'O', 213:'O', 214:'O', 216:'O',
        240:'o', 242:'o', 243:'o', 244:'o', 245:'o', 246:'o',
        217:'U', 218:'U', 219:'U', 220:'U',
        249:'u', 250:'u', 251:'u', 252:'u'
    };
    for (var i = 0, code; i < str.length; i++) {
        code = str.charCodeAt(i);
        if(code < 128) {
            result = result + str.charAt(i);
        }
        else {
            if(typeof convert[code] != 'undefined') {
                result = result + convert[code];   
            }
        }
    }
    return result;
};


/**
* Encode / Decode a string to base64url
*
*
*/
(function() {
    var BASE64_PADDING = '=';

    var BASE64_BINTABLE = [
      -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
      -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
      -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, 62, -1, -1, -1, 63,
      52, 53, 54, 55, 56, 57, 58, 59, 60, 61, -1, -1, -1,  0, -1, -1,
      -1,  0,  1,  2,  3,  4,  5,  6,  7,  8,  9, 10, 11, 12, 13, 14,
      15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, -1, -1, -1, -1, -1,
      -1, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40,
      41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, -1, -1, -1, -1, -1
    ];    
    
    var BASE64_CHARTABLE =
    'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_'.split('');


    String.prototype.base64_decode = function () {
        var result = '';
        var object = this;
        var leftbits = 0; // number of bits decoded, but yet to be appended
        var leftdata = 0; // bits decoded, but yet to be appended

        // Convert one by one.
        for (var i = 0; i < object.length; i += 1) {
            var code = object.charCodeAt(i);
            var value = BASE64_BINTABLE[code & 0x7F];
            // Skip LF(NL) || CR
            if (0x0A == code || 0x0D == code) continue;
            // Fail on illegal characters
            if (-1 === value) return null;
            // Collect data into leftdata, update bitcount
            leftdata = (leftdata << 6) | value;
            leftbits += 6;
            // If we have 8 or more bits, append 8 bits to the result
            if (leftbits >= 8) {
                leftbits -= 8;
                // Append if not padding.
                if (BASE64_PADDING !== object.charAt(i)) {
                  result += String.fromCharCode((leftdata >> leftbits) & 0xFF);
                }
                leftdata &= (1 << leftbits) - 1;
            }
        }
        // If there are any bits left, the base64 string was corrupted
        if (leftbits) return null;
        return result;
    };


    String.prototype.base64_encode = function () {
        var result = '', index, length, rest;
        var object = this;
        
        if(object.length < 3) return null;
        // Convert every three bytes to 4 ASCII characters.
        for (index = 0, length = object.length - 2; index < length; index += 3) {
            var char1 = object.charCodeAt(index), char2 = object.charCodeAt(index+1), char3 = object.charCodeAt(index+2);
            result += BASE64_CHARTABLE[char1 >> 2];
            result += BASE64_CHARTABLE[((char1 & 0x03) << 4) + (char2 >> 4)];
            result += BASE64_CHARTABLE[((char2 & 0x0F) << 2) + (char3 >> 6)];
            result += BASE64_CHARTABLE[char3 & 0x3F];
        }

        rest = object.length % 3;

        // Convert the remaining 1 or 2 bytes, padding out to 4 characters.
        if (0 !== rest) {
            index = object.length - rest;
            result += BASE64_CHARTABLE[object[index + 0] >> 2];
            var char1 = object.charCodeAt(index), char2 = object.charCodeAt(index+1);
            if (2 === rest) {
                result += BASE64_CHARTABLE[((char1 & 0x03) << 4) + (char2 >> 4)];
                result += BASE64_CHARTABLE[(char2 & 0x0F) << 2];
                result += BASE64_PADDING;
            } 
            else {
                result += BASE64_CHARTABLE[(char1 & 0x03) << 4];
                result += BASE64_PADDING + BASE64_PADDING;
            }
        }

        return result;
    };
    
})();