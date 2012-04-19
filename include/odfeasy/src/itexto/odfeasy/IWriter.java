/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

package itexto.odfeasy;

import java.io.File;
import java.io.OutputStream;

/**
 *
 * @author henriqueloboweissmann
 */
public interface IWriter {

    public void write(IODFDocument document, File file) throws OdfEasyException;
    
    public void write(IODFDocument document, OutputStream stream) throws OdfEasyException;
    
}
