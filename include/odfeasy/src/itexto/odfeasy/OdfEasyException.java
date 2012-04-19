/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

package itexto.odfeasy;

/**
 *
 * @author henriqueloboweissmann
 */
public class OdfEasyException extends Exception {

    private String message;
    
    public String getMessage() {return this.message;}
    
    public OdfEasyException(String message) {
        this.message = message;
    }
    
    private Exception exception;
    
    public Exception getException() {
        return this.exception;
    }
    
    public OdfEasyException(String message, Exception ex) {
        this.message = message;
        this.exception = ex;
    }
    
}
