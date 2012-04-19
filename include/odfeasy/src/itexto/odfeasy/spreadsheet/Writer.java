/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

package itexto.odfeasy.spreadsheet;

import itexto.odfeasy.IODFDocument;
import itexto.odfeasy.IWriter;
import itexto.odfeasy.OdfEasyException;
import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.OutputStream;
import java.util.List;
import org.openoffice.odf.doc.OdfSpreadsheetDocument;
import org.openoffice.odf.doc.element.office.OdfSpreadsheet;
import org.openoffice.odf.doc.element.table.OdfTable;
import org.openoffice.odf.doc.element.table.OdfTableCell;
import org.openoffice.odf.doc.element.table.OdfTableRow;
import org.openoffice.odf.doc.element.text.OdfParagraph;
import org.w3c.dom.NodeList;

/**
 *
 * @author henriqueloboweissmann
 */
public class Writer implements IWriter {

    public void write(IODFDocument document, File file) throws OdfEasyException {
        try {
            FileOutputStream stream = new FileOutputStream(file);
            write(document, stream);
        } catch (IOException ex) {
            throw new OdfEasyException("Error writing document", ex);
        }
    }

    private boolean isDocumentValid(IODFDocument doc) throws OdfEasyException {
        if (doc == null)
            throw new OdfEasyException("Document is null");
        
        if ((doc instanceof Document) == false) {
            throw new OdfEasyException("Not a spreadsheet document");
        }
        
        Document spreadsheetDocument = (Document) doc;
        
        if (spreadsheetDocument.getSpreadsheet() == null) {
            throw new OdfEasyException("Null document");
        }
        
        return true;
    }
    
    private OdfSpreadsheetDocument createOdfSpreadsheetDocument() throws Exception {
        OdfSpreadsheetDocument doc = new OdfSpreadsheetDocument();
        
        NodeList sheets = doc.getContentDom().getElementsByTagName("table:table");
        if (sheets != null && sheets.getLength() > 0) {
            for (int i = 0; i < sheets.getLength(); i++ ) {
                sheets.item(i).getParentNode().removeChild(sheets.item(i));
            }
        
        }
        
        return doc;
    }
    
    private OdfSpreadsheet getOdfSpreadsheet(OdfSpreadsheetDocument doc) {
        if (doc != null) {
            return (OdfSpreadsheet) doc.getContentDom().getElementsByTagName("office:spreadsheet").item(0);
        }
        return null;
    }
    
    private OdfTable createOdfTable(OdfSpreadsheetDocument doc, Table table) {
        if (doc != null && table != null) {
            OdfTable result = doc.getContentDom().createOdfElement(OdfTable.class);
            result.setName(table.getName());
            return result;
        }
        return null;
    }
    
    
    private OdfTableRow createEmptyRow(OdfSpreadsheetDocument doc, int repetition) {
        if (doc != null && repetition > 0) {
            OdfTableRow row = doc.getContentDom().createOdfElement(OdfTableRow.class);
            row.setAttribute("table:number-rows-repeated", Integer.toString(repetition));
            for (int i = 0; i < repetition; i++) {
                OdfTableCell cell = doc.getContentDom().createOdfElement(OdfTableCell.class);
                row.appendCell(cell);
                OdfParagraph paragraph = doc.getContentDom().createOdfElement(OdfParagraph.class);
                cell.appendChild(paragraph);
            }
        
            return row;
        }
        return null;
    }
    
    private OdfTableCell createEmptyCell(OdfSpreadsheetDocument doc, int repetition) throws Exception {
        if (doc != null && repetition > 0) {
            OdfTableCell result = doc.getContentDom().createOdfElement(OdfTableCell.class);
            result.setAttribute("table:number-columns-repeated", Integer.toString(repetition));
            OdfParagraph paragraph = doc.getContentDom().createOdfElement(OdfParagraph.class);
            result.appendChild(paragraph);
            return result;
        }
        return null;
    }
    
    private void processCells(OdfTableRow odfRow, OdfSpreadsheetDocument doc, List<Cell> cells) throws Exception {
        if (odfRow != null && doc != null && cells != null) {
            
            for (int i = 0; i < cells.size(); i++) {
                
                Cell cell = cells.get(i);
                int interval = 0;
                
                if (i == 0) {
                    interval = cell.getColumn();
                } else {
                    interval = cell.getColumn() - cells.get(i - 1).getColumn();
                }
                
                if (interval > 1) {
                    OdfTableCell emptyCells = this.createEmptyCell(doc, interval - 1);
                    odfRow.appendCell(emptyCells);
                }
                
                OdfTableCell odfCell = doc.getContentDom().createOdfElement(OdfTableCell.class);
                odfCell.setAttribute("office:value-type", cell.getDataType().getValue());
                
                OdfParagraph paragraph = doc.getContentDom().createOdfElement(OdfParagraph.class);
                paragraph.setTextContent(cell.getValue().toString());
                odfCell.appendChild(paragraph);
                odfRow.appendCell(odfCell);
                
            }
            
        }
    }
    
    private void processRows(OdfTable table, OdfSpreadsheetDocument doc, List<Row> rows) throws Exception {
        
        if (table != null && doc != null && rows != null) {
            int interval = 0;
            for (int i = 0; i < rows.size(); i++) {
                Row row = rows.get(i);
                
                if (i == 0) {
                    interval = rows.get(i).getOrder();
                } else {
                    interval = rows.get(i).getOrder() - rows.get(i - 1).getOrder();
                }
                
                if (interval > 1) {
                    OdfTableRow emptyRows = this.createEmptyRow(doc, interval - 1);
                    table.appendRow(emptyRows);
                }
                
                OdfTableRow odfRow = doc.getContentDom().createOdfElement(OdfTableRow.class);
                table.appendRow(odfRow);
                
                processCells(odfRow, doc, row.getCells());
                
                
            }
        }
        
    }
    
    public void write(IODFDocument document, OutputStream stream) throws OdfEasyException {
        if (isDocumentValid(document) && stream != null) {
            
            try {
                OdfSpreadsheetDocument doc = createOdfSpreadsheetDocument();
                
                Document spreadsheetDoc = (Document) document;
                
               Spreadsheet sheet = spreadsheetDoc.getSpreadsheet();
               OdfSpreadsheet odfSheet = this.getOdfSpreadsheet(doc);
               
               for (Table table : sheet.getTables()) {
                   OdfTable odfTable = this.createOdfTable(doc, table);
                   odfSheet.appendChild(odfTable);
                   
                   if (table.getRows().size() > 0) {
                       table.sortRows();
                       
                       processRows(odfTable, doc, table.getRows());
                       
                   }
                   
               }
               
                
                doc.save(stream);
            } catch (Exception ex) {
                throw new OdfEasyException("Error writing document", ex);
            }
            
        } else {
            throw new OdfEasyException("Document or stream null");
        }
    }

}
